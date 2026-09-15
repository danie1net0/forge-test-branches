<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Services;

use Closure;
use Ddr\ForgeTestBranches\Data\{CreateDatabaseData, CreateDatabaseUserData, CreateSiteData, DatabaseData, DatabaseUserData, DomainData, EnvironmentData, SiteData};
use Ddr\ForgeTestBranches\Exceptions\{ConfigurationException, DomainNotFoundException};
use Ddr\ForgeTestBranches\Integrations\Forge\ForgeClient;
use Ddr\ForgeTestBranches\Logger;
use Illuminate\Support\Str;
use RuntimeException;
use Saloon\Exceptions\Request\Statuses\NotFoundException;
use Throwable;

class EnvironmentBuilder
{
    /** @var array<int, string> */
    private const array VALID_SITE_TYPES = [
        'laravel', 'symfony', 'statamic', 'wordpress', 'phpmyadmin',
        'php', 'nextjs', 'nuxtjs', 'static-html', 'other', 'custom',
    ];

    /** @var array<int, string> */
    private const array VALID_GIT_PROVIDERS = ['gitlab', 'github', 'bitbucket'];

    /**
     * MySQL truncates identifiers at 63 bytes, but usernames are capped at
     * 32 characters — well below the 63 the Forge API allows for a database
     * name. The same generated name is reused for both, so it is truncated
     * to the tighter limit.
     */
    private const int MAX_MYSQL_IDENTIFIER_LENGTH = 32;

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
        $this->assertConfigurationIsValid();

        $slug = $this->sanitizer->sanitize($branch);
        $domain = $this->domainBuilder->build($slug);
        $serverId = (int) config('forge-test-branches.server_id');

        $this->logger->info('Creating environment', ['branch' => $branch, 'domain' => $domain, 'server_id' => $serverId]);

        $database = null;
        $databaseUser = null;
        $site = null;

        try {
            $database = $this->createDatabase($serverId, $slug);
            $this->forge->databases()->waitForInstallation($serverId, $database->id);
            $this->logger->debug('Database created', ['database' => $database->name]);

            [$databaseUser, $databasePassword] = $this->createDatabaseUser($serverId, $slug, $database);
            $this->forge->databaseUsers()->waitForInstallation($serverId, $databaseUser->id);
            $this->logger->debug('Database user created', ['user' => $databaseUser->name]);

            $site = $this->createSite($serverId, $domain, $branch);
            $this->logger->debug('Site created', ['site_id' => $site->id, 'domain' => $domain]);

            $this->forge->sites()->waitForInstallation($serverId, $site->id);
            $this->logger->debug('Site installed with git repository', ['branch' => $branch]);

            $this->updateEnvironmentFile($serverId, $site->id, $database->name, $databaseUser->name, $databasePassword, $slug);
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

        $site = $this->forge->sites()->findByName($serverId, $domain);

        if (! $site instanceof SiteData || $site->isBeingRemoved()) {
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

        $errors = array_filter([
            $this->deleteSite($environment),
            $this->deleteDatabaseUser($environment),
            $this->deleteDatabase($environment),
        ]);

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
        $name = $prefix . str_replace('-', '_', $slug);

        if (mb_strlen($name) <= self::MAX_MYSQL_IDENTIFIER_LENGTH) {
            return $name;
        }

        $hash = mb_substr(md5($slug), 0, 6);
        $availableLength = self::MAX_MYSQL_IDENTIFIER_LENGTH - mb_strlen($prefix) - mb_strlen($hash) - 1;
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
                databaseIds: [$database->id]
            )
        );

        return [$user, $password];
    }

    protected function createSite(int $serverId, string $domain, string $branch): SiteData
    {
        return $this->forge->sites()->create(
            $serverId,
            new CreateSiteData(
                name: $domain,
                type: (string) config('forge-test-branches.site.project_type'),
                webDirectory: (string) config('forge-test-branches.site.directory'),
                isIsolated: (bool) config('forge-test-branches.site.isolated'),
                phpVersion: (string) config('forge-test-branches.site.php_version'),
                sourceControlProvider: (string) config('forge-test-branches.git.provider'),
                repository: (string) config('forge-test-branches.git.repository'),
                branch: $branch,
                installComposerDependencies: true,
                zeroDowntimeDeployments: (bool) config('forge-test-branches.site.zero_downtime_deployments', false),
            )
        );
    }

    protected function updateEnvironmentFile(int $serverId, int $siteId, string $databaseName, string $databaseUser, string $databasePassword, string $slug): void
    {
        $currentEnv = $this->forge->sites()->getEnvironmentFile($serverId, $siteId);

        $envVariables = [
            'APP_ENV' => 'staging',
            'APP_DEBUG' => 'true',
            'DB_DATABASE' => $databaseName,
            'DB_USERNAME' => $databaseUser,
            'DB_PASSWORD' => $databasePassword,
        ];

        $envVariables = array_merge($envVariables, $this->buildEnvironmentVariables($slug));

        $updatedEnv = $this->mergeEnvVariables($currentEnv, $envVariables);

        $this->forge->sites()->updateEnvironmentFile($serverId, $siteId, $updatedEnv);

        // The write above is asynchronous (202): confirm it actually landed
        // before the deploy script runs and reads the file.
        $this->forge->sites()->waitForEnvironmentFileContaining($serverId, $siteId, "DB_DATABASE={$databaseName}");
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

        $formattedVariables = array_map(
            $this->formatEnvironmentValue(...),
            $newVariables,
        );

        $merged = array_merge($existing, $formattedVariables);

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
        $domainRecord = $this->forge->domains()->findByName($serverId, $siteId, $domain);

        if (! $domainRecord instanceof DomainData) {
            throw DomainNotFoundException::onSite($domain);
        }

        $this->forge->domains()->waitForEnabled($serverId, $siteId, $domainRecord->id);

        $verificationMethod = (string) config('forge-test-branches.ssl.verification_method', 'http-01');
        $keyType = (string) config('forge-test-branches.ssl.key_type', 'ecdsa');
        $certificate = $this->forge->domains()->obtainLetsEncryptCertificate($serverId, $siteId, $domainRecord->id, $verificationMethod, $keyType);
        $this->forge->domains()->waitForCertificateActivation($serverId, $siteId, $domainRecord->id, $certificate->id);
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

    /**
     * Forge validates the file it receives with phpdotenv, which rejects
     * unquoted values containing whitespace, quotes, a backslash or `#`,
     * and interpolates `$` in both unquoted and double-quoted values. A
     * value needing quotes is therefore single-quoted to stay literal;
     * double quotes are used only when the value itself contains a single
     * quote, escaping what phpdotenv still treats specially inside them.
     *
     * mergeEnvVariables() re-reads the current file split by line, so a
     * literal newline inside a value would corrupt it on the next update
     * even though phpdotenv itself allows a newline inside quotes.
     */
    private function formatEnvironmentValue(string $value): string
    {
        $value = str_replace(["\r\n", "\r", "\n"], ' ', $value);

        if (preg_match('/[\s#\'"\\\\$]/', $value) !== 1) {
            return $value;
        }

        if (! str_contains($value, "'")) {
            return "'{$value}'";
        }

        return '"' . addcslashes($value, '"\\$') . '"';
    }

    private function assertConfigurationIsValid(): void
    {
        $serverId = config('forge-test-branches.server_id');

        if (! is_numeric($serverId) || (int) $serverId <= 0) {
            throw ConfigurationException::missingServerId();
        }

        $projectType = (string) config('forge-test-branches.site.project_type');

        if (! in_array($projectType, self::VALID_SITE_TYPES, true)) {
            throw ConfigurationException::invalidProjectType($projectType);
        }

        $gitProvider = (string) config('forge-test-branches.git.provider');

        if (! in_array($gitProvider, self::VALID_GIT_PROVIDERS, true)) {
            throw ConfigurationException::invalidGitProvider($gitProvider);
        }

        $repository = config('forge-test-branches.git.repository');

        if (! is_string($repository) || $repository === '') {
            throw ConfigurationException::missingRepository();
        }
    }

    private function deleteSite(EnvironmentData $environment): ?string
    {
        return $this->deleteResourceQuietly(
            label: 'Site',
            context: ['site_id' => $environment->siteId],
            delete: function () use ($environment): void {
                $this->forge->sites()->delete($environment->serverId, $environment->siteId);
            },
        );
    }

    private function deleteDatabaseUser(EnvironmentData $environment): ?string
    {
        $databaseUserId = $environment->databaseUserId;

        if ($databaseUserId === null) {
            return null;
        }

        return $this->deleteResourceQuietly(
            label: 'Database user',
            context: ['user_id' => $databaseUserId],
            delete: function () use ($environment, $databaseUserId): void {
                $this->forge->databaseUsers()->delete($environment->serverId, $databaseUserId);
            },
        );
    }

    private function deleteDatabase(EnvironmentData $environment): ?string
    {
        $databaseId = $environment->databaseId;

        if ($databaseId === null) {
            return null;
        }

        return $this->deleteResourceQuietly(
            label: 'Database',
            context: ['database_id' => $databaseId],
            delete: function () use ($environment, $databaseId): void {
                $this->forge->databases()->delete($environment->serverId, $databaseId);
            },
        );
    }

    /**
     * Deletes a resource, tolerating that it may already be gone (404).
     * Returns null on success, or an error message prefixed with the label
     * so the caller can aggregate failures across multiple resources.
     *
     * @param array<string, mixed> $context
     * @param Closure(): void $delete
     */
    private function deleteResourceQuietly(string $label, array $context, Closure $delete): ?string
    {
        try {
            $delete();
        } catch (NotFoundException) {
            $this->logger->debug("{$label} already removed", $context);

            return null;
        } catch (Throwable $throwable) {
            $this->logger->error('Failed to delete ' . mb_strtolower($label), [...$context, 'error' => $throwable->getMessage()]);

            return "{$label}: {$throwable->getMessage()}";
        }

        return null;
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
