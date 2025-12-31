<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Commands;

use Ddr\ForgeTestBranches\Models\ReviewEnvironment;
use Ddr\ForgeTestBranches\Services\{BranchPatternMatcher, EnvironmentBuilder};
use Illuminate\Console\Command;
use Throwable;

class CreateEnvironmentCommand extends Command
{
    protected $signature = 'forge-test-branches:create {--branch= : Branch name}';

    protected $description = 'Creates a review environment for the specified branch';

    public function handle(EnvironmentBuilder $builder, BranchPatternMatcher $patternMatcher): int
    {
        $branch = $this->option('branch') ?? getenv('CI_COMMIT_REF_NAME') ?: null;

        if (! $branch) {
            $this->error('Branch not specified. Use --branch=branch-name or set CI_COMMIT_REF_NAME');

            return self::FAILURE;
        }

        if (! $patternMatcher->isAllowed($branch)) {
            $this->warn("Branch does not match allowed patterns: {$branch}");

            return self::SUCCESS;
        }

        $existing = ReviewEnvironment::query()->where('branch', $branch)->first();

        if ($existing) {
            $this->warn("Environment already exists for branch: {$branch}");
            $this->info("URL: https://{$existing->domain}");

            return self::SUCCESS;
        }

        $this->info("Creating environment for branch: {$branch}");

        try {
            $environment = $builder->create($branch);

            $this->info('Environment created successfully!');
            $this->info("URL: https://{$environment->domain}");

            return self::SUCCESS;
        } catch (Throwable $throwable) {
            $this->error("Error creating environment: {$throwable->getMessage()}");

            return self::FAILURE;
        }
    }
}
