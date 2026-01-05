<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Services;

use Ddr\ForgeTestBranches\Data\{CreateDatabaseData, CreateDatabaseUserData, CreateSiteData, DatabaseData, DatabaseUserData, EnvironmentData, InstallGitRepositoryData, SiteData};
use Ddr\ForgeTestBranches\Integrations\Forge\ForgeClient;
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

    public function create(string $branch): EnvironmentData
    {
        $slug = $this->sanitizer->sanitize($branch);
        $domain = $this->domainBuilder->build($slug);
        $serverId = (int) config('forge-test-branches.server_id');

        $database = $this->createDatabase($serverId, $slug);
        [$databaseUser, $databasePassword] = $this->createDatabaseUser($serverId, $slug, $database);
        $site = $this->createSite($serverId, $domain);
        $this->installGitRepository($serverId, $site->id, $branch);
        $this->forge->sites()->waitForRepositoryInstallation($serverId, $site->id);
        $this->updateEnvironment($serverId, $site->id, $database->name, $databaseUser->name, $databasePassword, $slug);
        $this->updateDeploymentScript($serverId, $site->id, $branch);

        if (config('forge-test-branches.ssl.enabled')) {
            $this->obtainSslCertificate($serverId, $site->id, $domain);
        }

        if (config('forge-test-branches.deploy.quick_deploy')) {
            $this->forge->sites()->enableQuickDeploy($serverId, $site->id);
        }

        $this->forge->sites()->deploy($serverId, $site->id);

        return new EnvironmentData(
            branch: $branch,
            slug: $slug,
            domain: $domain,
            serverId: $serverId,
            siteId: $site->id,
            databaseId: $database->id,
            databaseUserId: $databaseUser->id,
        );
    }

    public function find(string $branch): ?EnvironmentData
    {
        $slug = $this->sanitizer->sanitize($branch);
        $domain = $this->domainBuilder->build($slug);
        $serverId = (int) config('forge-test-branches.server_id');

        $site = $this->forge->sites()->findByDomain($serverId, $domain);

        if (! $site instanceof SiteData) {
            return null;
        }

        $databaseName = $this->buildDatabaseName($slug);
        $database = $this->forge->databases()->findByName($serverId, $databaseName);
        $databaseUser = $this->forge->databaseUsers()->findByName($serverId, $databaseName);

        return new EnvironmentData(
            branch: $branch,
            slug: $slug,
            domain: $domain,
            serverId: $serverId,
            siteId: $site->id,
            databaseId: $database?->id,
            databaseUserId: $databaseUser?->id,
        );
    }

    public function exists(string $branch): bool
    {
        return $this->find($branch) instanceof EnvironmentData;
    }

    public function destroy(EnvironmentData $environment): void
    {
        $this->forge->sites()->delete($environment->serverId, $environment->siteId);

        if ($environment->databaseUserId) {
            $this->forge->databaseUsers()->delete($environment->serverId, $environment->databaseUserId);
        }

        if ($environment->databaseId) {
            $this->forge->databases()->delete($environment->serverId, $environment->databaseId);
        }
    }

    public function deploy(EnvironmentData $environment): void
    {
        $this->forge->sites()->deploy($environment->serverId, $environment->siteId);
    }

    protected function buildDatabaseName(string $slug): string
    {
        $prefix = (string) config('forge-test-branches.database.prefix');
        $maxLength = 32;
        $name = $prefix . str_replace('-', '_', $slug);

        if (mb_strlen($name) <= $maxLength) {
            return $name;
        }

        $hash = mb_substr(md5($slug), 0, 6);
        $availableLength = $maxLength - mb_strlen($prefix) - mb_strlen($hash) - 1;
        $truncatedSlug = mb_substr(str_replace('-', '_', $slug), 0, $availableLength);
        $truncatedSlug = mb_rtrim($truncatedSlug, '_');

        return $prefix . $truncatedSlug . '_' . $hash;
    }

    protected function createDatabase(int $serverId, string $slug): DatabaseData
    {
        $name = $this->buildDatabaseName($slug);

        return $this->forge->databases()->create(
            $serverId,
            new CreateDatabaseData(name: $name)
        );
    }

    /** @return array{DatabaseUserData, string} */
    protected function createDatabaseUser(int $serverId, string $slug, DatabaseData $database): array
    {
        $username = $this->buildDatabaseName($slug);
        $password = Str::password(32, letters: true, numbers: true, symbols: false, spaces: false);

        $user = $this->forge->databaseUsers()->create(
            $serverId,
            new CreateDatabaseUserData(
                name: $username,
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

        $envVariables = array_merge($envVariables, $this->buildEnvironmentVariables($slug));

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

    protected function obtainSslCertificate(int $serverId, int $siteId, string $domain): void
    {
        $certificate = $this->forge->sites()->obtainLetsEncryptCertificate($serverId, $siteId, [$domain]);
        $this->forge->sites()->waitForCertificateActivation($serverId, $siteId, $certificate->id);
    }

    /**
     * @return array<string, string>
     */
    protected function buildEnvironmentVariables(string $slug): array
    {
        $customVariables = config('forge-test-branches.env_variables', []);

        if (! is_array($customVariables)) {
            return [];
        }

        $variables = [];

        foreach ($customVariables as $key => $value) {
            $processed = str_replace('{slug}', $slug, (string) $value);
            $processed = $this->replaceEnvPlaceholders($processed);
            $variables[$key] = $processed;
        }

        return $variables;
    }

    protected function replaceEnvPlaceholders(string $value): string
    {
        return (string) preg_replace_callback(
            '/\{env:([A-Z_]+)\}/',
            function (array $matches): string {
                $envValue = env($matches[1]);

                return $envValue !== null ? (string) $envValue : '';
            },
            $value
        );
    }
}
