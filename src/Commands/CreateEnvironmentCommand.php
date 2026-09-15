<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Commands;

use Ddr\ForgeTestBranches\Commands\Concerns\ResolvesForgeDependencies;
use Ddr\ForgeTestBranches\Data\EnvironmentData;
use Ddr\ForgeTestBranches\Services\{BranchPatternMatcher, EnvironmentBuilder};
use Illuminate\Console\Command;
use Throwable;

class CreateEnvironmentCommand extends Command
{
    use ResolvesForgeDependencies;

    protected $signature = 'forge-test-branches:create {--branch= : Branch name}';

    protected $description = 'Creates a review environment for the specified branch';

    public function handle(BranchPatternMatcher $patternMatcher): int
    {
        $branch = $this->option('branch') ?? getenv('CI_COMMIT_REF_NAME') ?: null;

        if (! is_string($branch)) {
            $this->error('Branch not specified. Use --branch=branch-name or set CI_COMMIT_REF_NAME');

            return self::FAILURE;
        }

        if (! $patternMatcher->isAllowed($branch)) {
            $this->warn("Branch does not match allowed patterns: {$branch}");

            return self::SUCCESS;
        }

        $builder = $this->resolveOrFail(EnvironmentBuilder::class);

        if ($builder === null) {
            return self::FAILURE;
        }

        $existingEnvironment = $builder->find($branch);

        if ($existingEnvironment instanceof EnvironmentData) {
            $this->warn("Environment already exists for branch: {$branch}");
            $this->info("URL: https://{$existingEnvironment->domain}");

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
