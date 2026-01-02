<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Services;

class DeploymentScriptBuilder
{
    public function build(string $branch): string
    {
        $customScript = config('forge-test-branches.deploy.script');

        if ($customScript) {
            return str_replace('{branch}', $branch, $customScript);
        }

        return $this->defaultScript($branch);
    }

    protected function defaultScript(string $branch): string
    {
        $seedCommand = $this->buildSeedCommand();

        return <<<BASH
        cd \$FORGE_SITE_PATH
        git fetch origin {$branch}
        git reset --hard origin/{$branch}
        git clean -fd

        \$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

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

    protected function buildSeedCommand(): string
    {
        if (! config('forge-test-branches.deploy.seed')) {
            return '';
        }

        $seedClass = config('forge-test-branches.deploy.seed_class');

        if ($seedClass) {
            return "\n        \$FORGE_PHP artisan db:seed --class={$seedClass} --force";
        }

        return "\n        \$FORGE_PHP artisan db:seed --force";
    }
}
