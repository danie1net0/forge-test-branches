<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Services;

use Ddr\ForgeTestBranches\Data\{CreateDatabaseData, CreateDatabaseUserData, CreateSiteData, DatabaseData, DatabaseUserData, EnvironmentData, InstallGitRepositoryData, SiteData};
use Ddr\ForgeTestBranches\Integrations\Forge\ForgeClient;
use Ddr\ForgeTestBranches\Logger;
use Illuminate\Support\{Sleep, Str};
use RuntimeException;
use Throwable;

class EnvironmentBuilder
{
    public function __construct(
        protected ForgeClient $forge,
        protected BranchSanitizer $sanitizer,
        protected DomainBuilder $domainBuilder,
        protected DeploymentScriptBuilder $scriptBuilder,
        protected Logger $logger,
    ) {
    }

    public function create(string $branch): EnvironmentData
    {
        $slug = $this->sanitizer->sanitize($branch);
        $domain = $this->domainBuilder->build($slug);
        $serverId = (int) config('forge-test-branches.server_id');

        $this->logger->info('Creating environment', ['branch' => $branch, 'domain' => $domain, 'server_id' => $serverId]);

        $database = null;
        $databaseUser = null;
        $site = null;

        try {
            $database = $this->createDatabase($serverId, $slug);
            $this->logger->debug('Database created', ['database' => $database->name]);

            [$databaseUser, $databasePassword] = $this->createDatabaseUser($serverId, $slug, $database);
            $this->logger->debug('Database user created', ['user' => $databaseUser->name]);

            $site = $this->createSite($serverId, $domain);
            $this->logger->debug('Site created', ['site_id' => $site->id, 'domain' => $domain]);

            $this->installGitRepository($serverId, $site->id, $branch);
            $this->forge->sites()->waitForRepositoryInstallation($serverId, $site->id);
            $this->logger->debug('Git repository installed', ['branch' => $branch]);

            $this->waitForForgeProvisioning($serverId, $site->id);

            $this->updateEnvironment($serverId, $site->id, $database->name, $databaseUser->name, $databasePassword, $slug);
            $this->updateDeploymentScript($serverId, $site->id, $branch);

            if (config('forge-test-branches.ssl.enabled') === true) {
                $this->obtainSslCertificate($serverId, $site->id, $domain);
                $this->logger->debug('SSL certificate obtained', ['domain' => $domain]);
            }

            if (config('forge-test-branches.deploy.quick_deploy') === true) {
                $this->forge->sites()->enableQuickDeploy($serverId, $site->id);
            }

            $this->forge->sites()->deploy($serverId, $site->id);
            $this->logger->info('Environment created', ['branch' => $branch, 'domain' => $domain, 'site_id' => $site->id]);
        } catch (Throwable $throwable) {
            $this->logger->error('Environment creation failed, rolling back', ['branch' => $branch, 'error' => $throwable->getMessage()]);
            $this->rollbackCreation($serverId, $site, $database, $databaseUser);

            throw $throwable;
        }

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

    /** @return array<EnvironmentData> */
    public function listAll(): array
    {
        $serverId = (int) config('forge-test-branches.server_id');
        $sites = $this->forge->sites()->list($serverId);

        $environments = [];

        foreach ($sites as $site) {
            $slug = $this->domainBuilder->extractSlugFromDomain($site->name);

            if ($slug === null) {
                continue;
            }

            $environments[] = new EnvironmentData(
                branch: $site->repositoryBranch ?? $slug,
                slug: $slug,
                domain: $site->name,
                serverId: $serverId,
                siteId: $site->id,
            );
        }

        $this->logger->debug('Listed environments', ['count' => count($environments)]);

        return $environments;
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
        $this->logger->info('Destroying environment', ['branch' => $environment->branch, 'domain' => $environment->domain, 'site_id' => $environment->siteId]);

        $errors = [];

        try {
            $this->forge->sites()->delete($environment->serverId, $environment->siteId);
        } catch (Throwable $throwable) {
            $errors[] = "Site: {$throwable->getMessage()}";
            $this->logger->error('Failed to delete site', ['site_id' => $environment->siteId, 'error' => $throwable->getMessage()]);
        }

        if ($environment->databaseUserId !== null) {
            try {
                $this->forge->databaseUsers()->delete($environment->serverId, $environment->databaseUserId);
            } catch (Throwable $throwable) {
                $errors[] = "Database user: {$throwable->getMessage()}";
                $this->logger->error('Failed to delete database user', ['user_id' => $environment->databaseUserId, 'error' => $throwable->getMessage()]);
            }
        }

        if ($environment->databaseId !== null) {
            try {
                $this->forge->databases()->delete($environment->serverId, $environment->databaseId);
            } catch (Throwable $throwable) {
                $errors[] = "Database: {$throwable->getMessage()}";
                $this->logger->error('Failed to delete database', ['database_id' => $environment->databaseId, 'error' => $throwable->getMessage()]);
            }
        }

        if ($errors !== []) {
            throw new RuntimeException('Partial destruction: ' . implode('; ', $errors));
        }

        $this->logger->info('Environment destroyed', ['branch' => $environment->branch, 'domain' => $environment->domain]);
    }

    public function deploy(EnvironmentData $environment): void
    {
        $this->logger->info('Deploy started', ['branch' => $environment->branch, 'domain' => $environment->domain, 'site_id' => $environment->siteId]);

        $this->forge->sites()->deploy($environment->serverId, $environment->siteId);

        $this->logger->info('Deploy triggered', ['branch' => $environment->branch, 'domain' => $environment->domain]);
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
                projectType: (string) config('forge-test-branches.site.project_type'),
                directory: (string) config('forge-test-branches.site.directory'),
                isolated: (bool) config('forge-test-branches.site.isolated'),
                phpVersion: (string) config('forge-test-branches.site.php_version'),
            )
        );
    }

    protected function installGitRepository(int $serverId, int $siteId, string $branch): void
    {
        $this->forge->sites()->installGitRepository(
            $serverId,
            $siteId,
            new InstallGitRepositoryData(
                provider: (string) config('forge-test-branches.git.provider'),
                repository: (string) config('forge-test-branches.git.repository'),
                branch: $branch,
                composer: true,
            )
        );
    }

    protected function updateEnvironment(int $serverId, int $siteId, string $databaseName, string $databaseUser, string $databasePassword, string $slug): void
    {
        $currentEnv = $this->forge->sites()->getEnvironment($serverId, $siteId);

        $envVariables = [
            'APP_ENV' => 'staging',
            'APP_DEBUG' => 'true',
            'DB_DATABASE' => $databaseName,
            'DB_USERNAME' => $databaseUser,
            'DB_PASSWORD' => $databasePassword,
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

                if ($envValue === null) {
                    return '';
                }

                return (string) $envValue;
            },
            $value
        );
    }

    private function waitForForgeProvisioning(int $serverId, int $siteId, int $maxAttempts = 12, int $sleepSeconds = 5): void
    {
        $this->logger->debug('Waiting for Forge provisioning to complete');

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $site = $this->forge->sites()->get($serverId, $siteId);

            if ($site->status === 'installed' && $site->deploymentStatus === null) {
                $this->logger->debug('Forge provisioning completed');

                return;
            }

            Sleep::sleep($sleepSeconds);
        }

        $this->logger->warning('Forge provisioning wait timed out, proceeding anyway');
    }

    private function rollbackCreation(int $serverId, ?SiteData $site, ?DatabaseData $database, ?DatabaseUserData $databaseUser): void
    {
        if ($site instanceof SiteData) {
            try {
                $this->forge->sites()->delete($serverId, $site->id);
                $this->logger->debug('Rollback: site deleted', ['site_id' => $site->id]);
            } catch (Throwable $throwable) {
                $this->logger->error('Rollback: failed to delete site', ['site_id' => $site->id, 'error' => $throwable->getMessage()]);
            }
        }

        if ($databaseUser instanceof DatabaseUserData) {
            try {
                $this->forge->databaseUsers()->delete($serverId, $databaseUser->id);
                $this->logger->debug('Rollback: database user deleted', ['user_id' => $databaseUser->id]);
            } catch (Throwable $throwable) {
                $this->logger->error('Rollback: failed to delete database user', ['user_id' => $databaseUser->id, 'error' => $throwable->getMessage()]);
            }
        }

        if ($database instanceof DatabaseData) {
            try {
                $this->forge->databases()->delete($serverId, $database->id);
                $this->logger->debug('Rollback: database deleted', ['database_id' => $database->id]);
            } catch (Throwable $throwable) {
                $this->logger->error('Rollback: failed to delete database', ['database_id' => $database->id, 'error' => $throwable->getMessage()]);
            }
        }
    }
}
