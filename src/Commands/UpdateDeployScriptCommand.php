<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Commands;

use Ddr\ForgeTestBranches\Data\EnvironmentData;
use Ddr\ForgeTestBranches\Integrations\Forge\ForgeClient;
use Ddr\ForgeTestBranches\Services\{DeploymentScriptBuilder, EnvironmentBuilder};
use Illuminate\Console\Command;
use Throwable;

class UpdateDeployScriptCommand extends Command
{
    protected $signature = 'forge-test-branches:update-script {--branch= : Branch name}';

    protected $description = 'Updates the deploy script for an existing review environment';

    public function handle(
        EnvironmentBuilder $builder,
        DeploymentScriptBuilder $scriptBuilder,
        ForgeClient $forge,
    ): int {
        $branch = $this->option('branch') ?? getenv('CI_COMMIT_REF_NAME') ?: null;

        if (! $branch) {
            $this->error('Branch not specified. Use --branch=branch-name or set CI_COMMIT_REF_NAME');

            return self::FAILURE;
        }

        $environment = $builder->find($branch);

        if (! $environment instanceof EnvironmentData) {
            $this->error("Environment not found for branch: {$branch}");

            return self::FAILURE;
        }

        $this->info("Updating deploy script for branch: {$branch}");

        try {
            $script = $scriptBuilder->build($branch);
            $forge->sites()->updateDeploymentScript($environment->serverId, $environment->siteId, $script);

            $this->info('Deploy script updated successfully!');

            return self::SUCCESS;
        } catch (Throwable $throwable) {
            $this->error("Error updating deploy script: {$throwable->getMessage()}");

            return self::FAILURE;
        }
    }
}
