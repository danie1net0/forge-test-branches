<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Commands;

use Ddr\ForgeTestBranches\Data\EnvironmentData;
use Ddr\ForgeTestBranches\Services\EnvironmentBuilder;
use Illuminate\Console\Command;
use Throwable;

class DeployEnvironmentCommand extends Command
{
    protected $signature = 'forge-test-branches:deploy {--branch= : Branch name}';

    protected $description = 'Deploys to the review environment for the specified branch';

    public function handle(EnvironmentBuilder $builder): int
    {
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

        $this->info("Deploying to branch: {$branch}");

        try {
            $builder->deploy($environment);

            $this->info('Deploy started successfully!');

            return self::SUCCESS;
        } catch (Throwable $throwable) {
            $this->error("Error deploying: {$throwable->getMessage()}");

            return self::FAILURE;
        }
    }
}
