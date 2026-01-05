<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Commands;

use Illuminate\Console\Command;

class CreateComposerAuthCommand extends Command
{
    protected $signature = 'forge-test-branches:create-auth-json
                            {--cleanup : Remove auth.json after creation}';

    protected $description = 'Create Composer auth.json from configuration';

    public function handle(): int
    {
        if ($this->option('cleanup')) {
            return $this->cleanup();
        }

        $authConfig = config('forge-test-branches.composer_auth', []);

        if (empty($authConfig)) {
            $this->info('No composer_auth configuration found. Skipping auth.json creation.');

            return self::SUCCESS;
        }

        return $this->create($authConfig);
    }

    protected function create(array $authConfig): int
    {
        $processedConfig = $this->processPlaceholders($authConfig);

        $authJson = json_encode($processedConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($authJson === false) {
            $this->error('Failed to encode auth configuration to JSON.');

            return self::FAILURE;
        }

        $authPath = base_path('auth.json');

        if (file_put_contents($authPath, $authJson) === false) {
            $this->error('Failed to write auth.json file.');

            return self::FAILURE;
        }

        $this->info('Successfully created auth.json');

        return self::SUCCESS;
    }

    protected function cleanup(): int
    {
        $authPath = base_path('auth.json');

        if (! file_exists($authPath)) {
            return self::SUCCESS;
        }

        if (! unlink($authPath)) {
            $this->error('Failed to remove auth.json file.');

            return self::FAILURE;
        }

        $this->info('Successfully removed auth.json');

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    protected function processPlaceholders(array $config): array
    {
        $processed = [];

        foreach ($config as $key => $value) {
            if (is_array($value)) {
                $processed[$key] = $this->processPlaceholders($value);

                continue;
            }

            if (! is_string($value)) {
                $processed[$key] = $value;

                continue;
            }

            $processed[$key] = $this->replaceEnvPlaceholders($value);
        }

        return $processed;
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
