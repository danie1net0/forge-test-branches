<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Services;

use InvalidArgumentException;

class DeploymentScriptBuilder
{
    private const string BRANCH_PATTERN = '/^[a-zA-Z0-9\/_\-\.]+$/';

    private const string CLASS_NAME_PATTERN = '/^[a-zA-Z_\\\\][a-zA-Z0-9_\\\\]*$/';

    public function build(string $branch): string
    {
        $this->validateBranchName($branch);

        $customScript = config('forge-test-branches.deploy.script');

        if (is_string($customScript) && $customScript !== '') {
            return str_replace('{branch}', $branch, $customScript);
        }

        return $this->defaultScript($branch);
    }

    private function validateBranchName(string $branch): void
    {
        if (! preg_match(self::BRANCH_PATTERN, $branch)) {
            throw new InvalidArgumentException("Branch name contains invalid characters: {$branch}");
        }
    }

    private function defaultScript(string $branch): string
    {
        $seedCommand = $this->buildSeedCommand();

        return <<<BASH
        cd \$FORGE_SITE_PATH
        git fetch origin {$branch}
        git reset --hard origin/{$branch}
        git clean -fd

        if [ -f artisan ]; then
            \$FORGE_PHP artisan forge-test-branches:create-auth-json
        fi

        \$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

        if [ -f artisan ]; then
            \$FORGE_PHP artisan forge-test-branches:create-auth-json --cleanup
        fi

        ( flock -w 10 9 || exit 1
            echo 'Restarting FPM...'; sudo -S service \$FORGE_PHP_FPM reload ) 9>/tmp/fpmlock

        if [ -f artisan ]; then
            \$FORGE_PHP artisan migrate --force{$seedCommand}
            \$FORGE_PHP artisan config:cache
            \$FORGE_PHP artisan route:cache
            \$FORGE_PHP artisan view:cache
        fi
        BASH;
    }

    private function buildSeedCommand(): string
    {
        if (config('forge-test-branches.deploy.seed') !== true) {
            return '';
        }

        $seedClass = config('forge-test-branches.deploy.seed_class');

        if (! is_string($seedClass) || $seedClass === '') {
            return "\n        \$FORGE_PHP artisan db:seed --force";
        }

        if (! preg_match(self::CLASS_NAME_PATTERN, $seedClass)) {
            throw new InvalidArgumentException("Invalid seed class name: {$seedClass}");
        }

        return "\n        \$FORGE_PHP artisan db:seed --class={$seedClass} --force";
    }
}
