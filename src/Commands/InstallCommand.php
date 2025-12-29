<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

use function Laravel\Prompts\{confirm, password, select, text};

class InstallCommand extends Command
{
    protected $signature = 'forge-test-branches:install';

    protected $description = 'Installs and configures the Forge Test Branches package';

    public function handle(): int
    {
        $this->components->info('Installing Forge Test Branches...');

        $this->publishConfig();

        if (confirm('Would you like to configure environment variables now?', true)) {
            $this->configureEnv();
        }

        if (confirm('Would you like to add GitLab CI/CD configuration?', true)) {
            $this->configureGitlabCi();
        }

        $this->components->info('Installation completed!');
        $this->newLine();
        $this->components->bulletList([
            'Run migrations: php artisan migrate',
            'Configure the GitLab webhook as described in the README',
        ]);

        return self::SUCCESS;
    }

    protected function publishConfig(): void
    {
        $this->call('vendor:publish', [
            '--tag' => 'forge-test-branches-config',
        ]);

        $this->call('vendor:publish', [
            '--tag' => 'forge-test-branches-migrations',
        ]);
    }

    protected function configureEnv(): void
    {
        $envPath = base_path('.env');

        if (! File::exists($envPath)) {
            $this->components->error('.env file not found.');

            return;
        }

        $variables = $this->collectEnvVariables();

        $envContent = File::get($envPath);
        $additions = [];

        foreach ($variables as $key => $value) {
            if ($value === null) {
                continue;
            }

            if ($value === '') {
                continue;
            }

            if (! str_contains($envContent, "{$key}=")) {
                $additions[] = "{$key}={$value}";
            }
        }

        if ($additions === []) {
            $this->components->info('All variables already exist in .env.');

            return;
        }

        $envContent .= "\n# Forge Test Branches\n" . implode("\n", $additions) . "\n";
        File::put($envPath, $envContent);

        $this->components->info('Variables added to .env.');
    }

    /** @return array<string, string> */
    protected function collectEnvVariables(): array
    {
        $token = password(
            label: 'FORGE_API_TOKEN (Forge API Token)',
            required: true,
        );

        $serverId = text(
            label: 'FORGE_SERVER_ID (Server ID on Forge)',
            required: true,
            validate: fn (string $value): ?string => is_numeric($value) ? null : 'ID must be numeric.',
        );

        $domain = text(
            label: 'FORGE_REVIEW_DOMAIN (Base domain for review apps)',
            placeholder: 'review.mysite.com',
            required: true,
        );

        $provider = select(
            label: 'FORGE_GIT_PROVIDER (Git Provider)',
            options: [
                'gitlab' => 'GitLab',
                'github' => 'GitHub',
                'bitbucket' => 'Bitbucket',
            ],
            default: 'gitlab',
        );

        $repository = text(
            label: 'FORGE_GIT_REPOSITORY (Repository in user/repo format)',
            placeholder: 'my-user/my-repo',
            required: true,
        );

        $webhookSecret = text(
            label: 'FORGE_WEBHOOK_SECRET (Secret to validate webhooks - optional)',
            placeholder: 'Leave empty to auto-generate',
        );

        if ($webhookSecret === '' || $webhookSecret === '0') {
            $webhookSecret = bin2hex(random_bytes(16));
            $this->components->info("Generated secret: {$webhookSecret}");
        }

        return [
            'FORGE_API_TOKEN' => $token,
            'FORGE_SERVER_ID' => $serverId,
            'FORGE_REVIEW_DOMAIN' => $domain,
            'FORGE_GIT_PROVIDER' => (string) $provider,
            'FORGE_GIT_REPOSITORY' => $repository,
            'FORGE_WEBHOOK_SECRET' => $webhookSecret,
        ];
    }

    protected function configureGitlabCi(): void
    {
        $ciPath = base_path('.gitlab-ci.yml');
        $stubPath = __DIR__ . '/../../stubs/.gitlab-ci.review.yml';

        if (! File::exists($stubPath)) {
            $this->components->error('GitLab CI stub file not found.');

            return;
        }

        $defaultDomain = config('forge-test-branches.domain.base') ?? 'review.example.com';

        $domain = text(
            label: 'Domain for review apps in CI',
            placeholder: 'review.mysite.com',
            default: $defaultDomain,
            required: true,
        );

        $stubContent = File::get($stubPath);
        $stubContent = str_replace('review.example.com', $domain, $stubContent);

        if (! File::exists($ciPath)) {
            File::put($ciPath, $stubContent);
            $this->components->info('.gitlab-ci.yml file created.');

            return;
        }

        $existingContent = File::get($ciPath);

        if (str_contains($existingContent, 'forge-test-branches')) {
            $this->components->warn('Forge Test Branches configuration already exists in .gitlab-ci.yml.');

            return;
        }

        $reviewContent = $this->extractReviewJobContent($stubContent);

        if (! str_contains($existingContent, 'stages:')) {
            $existingContent = "stages:\n  - review\n\n" . $existingContent;
        }

        if (str_contains($existingContent, 'stages:') && ! str_contains($existingContent, 'review')) {
            $existingContent = preg_replace(
                '/stages:\n/',
                "stages:\n  - review\n",
                $existingContent
            );
        }

        $existingContent .= "\n" . $reviewContent;
        File::put($ciPath, $existingContent);

        $this->components->info('Review apps configuration added to .gitlab-ci.yml.');
    }

    protected function extractReviewJobContent(string $content): string
    {
        $lines = explode("\n", $content);
        $result = [];
        $inJob = false;

        foreach ($lines as $line) {
            if (str_starts_with($line, '#') && ! $inJob) {
                continue;
            }

            if (str_starts_with($line, 'stages:')) {
                continue;
            }

            if (preg_match('/^\s+-\s+review$/', $line)) {
                continue;
            }

            if (preg_match('/^[a-z_]+:/', $line)) {
                $inJob = true;
            }

            if ($inJob) {
                $result[] = $line;
            }
        }

        return implode("\n", $result);
    }
}
