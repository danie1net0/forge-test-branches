<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Commands;

use Ddr\ForgeTestBranches\Models\ReviewEnvironment;
use Ddr\ForgeTestBranches\Services\EnvironmentBuilder;
use Illuminate\Console\Command;
use Throwable;

class DestroyEnvironmentCommand extends Command
{
    protected $signature = 'forge-test-branches:destroy {--branch= : Branch name}';

    protected $description = 'Destroys the review environment for the specified branch';

    public function handle(EnvironmentBuilder $builder): int
    {
        $branch = $this->option('branch') ?? getenv('CI_COMMIT_REF_NAME') ?: null;

        if (! $branch) {
            $this->error('Branch not specified. Use --branch=branch-name or set CI_COMMIT_REF_NAME');

            return self::FAILURE;
        }

        $environment = ReviewEnvironment::query()->where('branch', $branch)->first();

        if (! $environment) {
            $this->warn("Environment not found for branch: {$branch}");

            return self::SUCCESS;
        }

        $this->info("Destroying environment for branch: {$branch}");

        try {
            $builder->destroy($environment);

            $this->info('Environment destroyed successfully!');

            return self::SUCCESS;
        } catch (Throwable $throwable) {
            $this->error("Error destroying environment: {$throwable->getMessage()}");

            return self::FAILURE;
        }
    }
}
