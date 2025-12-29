<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Services;

use Ddr\ForgeTestBranches\Data\{CreateDatabaseData, CreateDatabaseUserData, CreateSiteData, DatabaseData, DatabaseUserData, InstallGitRepositoryData, SiteData};
use Ddr\ForgeTestBranches\Integrations\Forge\ForgeClient;
use Ddr\ForgeTestBranches\Models\ReviewEnvironment;
use Illuminate\Support\Str;

class EnvironmentBuilder
{
    public function __construct(
        protected ForgeClient $forge,
        protected BranchSanitizer $sanitizer,
        protected DomainBuilder $domainBuilder,
        protected DeploymentScriptBuilder $scriptBuilder,
    ) {
    }

    public function create(string $branch): ReviewEnvironment
    {
        $slug = $this->sanitizer->sanitize($branch);
        $domain = $this->domainBuilder->build($slug);
        $serverId = (int) config('forge-test-branches.server_id');

        $database = $this->createDatabase($serverId, $slug);
        [$databaseUser, $databasePassword] = $this->createDatabaseUser($serverId, $slug, $database);
        $site = $this->createSite($serverId, $domain);
        $this->installGitRepository($serverId, $site->id, $branch);
        $this->updateEnvironment($serverId, $site->id, $database->name, $databaseUser->name, $databasePassword, $slug);
        $this->updateDeploymentScript($serverId, $site->id, $branch);

        if (config('forge-test-branches.deploy.quick_deploy')) {
            $this->forge->sites()->enableQuickDeploy($serverId, $site->id);
        }

        $this->forge->sites()->deploy($serverId, $site->id);

        return ReviewEnvironment::query()->create([
            'branch' => $branch,
            'slug' => $slug,
            'domain' => $domain,
            'server_id' => $serverId,
            'site_id' => $site->id,
            'database_id' => $database->id,
            'database_user_id' => $databaseUser->id,
        ]);
    }

    public function destroy(ReviewEnvironment $environment): void
    {
        $this->forge->sites()->delete($environment->server_id, $environment->site_id);
        $this->forge->databaseUsers()->delete($environment->server_id, $environment->database_user_id);
        $this->forge->databases()->delete($environment->server_id, $environment->database_id);

        $environment->delete();
    }

    public function deploy(ReviewEnvironment $environment): void
    {
        $this->forge->sites()->deploy($environment->server_id, $environment->site_id);
    }

    protected function createDatabase(int $serverId, string $slug): DatabaseData
    {
        $prefix = config('forge-test-branches.database.prefix');
        $name = $prefix . str_replace('-', '_', $slug);

        return $this->forge->databases()->create(
            $serverId,
            new CreateDatabaseData(name: $name)
        );
    }

    /** @return array{DatabaseUserData, string} */
    protected function createDatabaseUser(int $serverId, string $slug, DatabaseData $database): array
    {
        $prefix = config('forge-test-branches.database.prefix');
        $name = $prefix . str_replace('-', '_', $slug);
        $password = Str::password(32);

        $user = $this->forge->databaseUsers()->create(
            $serverId,
            new CreateDatabaseUserData(
                name: $name,
                password: $password,
                databases: [$database->id]
            )
        );

        return [$user, $password];
    }

    protected function createSite(int $serverId, string $domain): SiteData
    {
        return $this->forge->sites()->create(
            $serverId,
            new CreateSiteData(
                domain: $domain,
                projectType: config('forge-test-branches.site.project_type'),
                directory: config('forge-test-branches.site.directory'),
                isolated: config('forge-test-branches.site.isolated'),
                phpVersion: config('forge-test-branches.site.php_version'),
            )
        );
    }

    protected function installGitRepository(int $serverId, int $siteId, string $branch): void
    {
        $this->forge->sites()->installGitRepository(
            $serverId,
            $siteId,
            new InstallGitRepositoryData(
                provider: config('forge-test-branches.git.provider'),
                repository: config('forge-test-branches.git.repository'),
                branch: $branch,
                composer: true,
            )
        );
    }

    protected function updateEnvironment(int $serverId, int $siteId, string $dbName, string $dbUser, string $dbPassword, string $slug): void
    {
        $currentEnv = $this->forge->sites()->getEnvironment($serverId, $siteId);

        $envVariables = [
            'APP_ENV' => 'staging',
            'APP_DEBUG' => 'true',
            'DB_DATABASE' => $dbName,
            'DB_USERNAME' => $dbUser,
            'DB_PASSWORD' => $dbPassword,
        ];

        $customVariables = config('forge-test-branches.env_variables', []);

        foreach ($customVariables as $key => $value) {
            $envVariables[$key] = str_replace('{slug}', $slug, $value);
        }

        $updatedEnv = $this->mergeEnvVariables($currentEnv, $envVariables);

        $this->forge->sites()->updateEnvironment($serverId, $siteId, $updatedEnv);
    }

    /** @param array<string, string> $newVariables */
    protected function mergeEnvVariables(string $currentEnv, array $newVariables): string
    {
        $lines = explode("\n", $currentEnv);
        $existing = [];

        foreach ($lines as $line) {
            if (in_array(mb_trim($line), ['', '0'], true)) {
                continue;
            }

            if (str_starts_with(mb_trim($line), '#')) {
                continue;
            }

            $parts = explode('=', $line, 2);

            if (count($parts) === 2) {
                $existing[$parts[0]] = $parts[1];
            }
        }

        $merged = array_merge($existing, $newVariables);

        $result = [];

        foreach ($merged as $key => $value) {
            $result[] = "{$key}={$value}";
        }

        return implode("\n", $result);
    }

    protected function updateDeploymentScript(int $serverId, int $siteId, string $branch): void
    {
        $script = $this->scriptBuilder->build($branch);
        $this->forge->sites()->updateDeploymentScript($serverId, $siteId, $script);
    }
}
