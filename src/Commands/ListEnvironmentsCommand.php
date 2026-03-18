<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Commands;

use Ddr\ForgeTestBranches\Data\EnvironmentData;
use Ddr\ForgeTestBranches\Services\{EnvironmentBuilder, RemoteBranchResolver};
use Illuminate\Console\Command;
use Throwable;

class ListEnvironmentsCommand extends Command
{
    protected $signature = 'forge-test-branches:list
        {--orphans : Show only orphaned environments (branch no longer exists on remote)}
        {--destroy-orphans : Destroy all orphaned environments}';

    protected $description = 'Lists review environments on the server';

    public function handle(EnvironmentBuilder $builder, RemoteBranchResolver $remoteBranchResolver): int
    {
        $this->info('Fetching review environments...');

        try {
            $environments = $builder->listAll();
        } catch (Throwable $throwable) {
            $this->error("Error fetching environments: {$throwable->getMessage()}");

            return self::FAILURE;
        }

        if ($environments === []) {
            $this->info('No review environments found.');

            return self::SUCCESS;
        }

        $shouldFilterOrphans = $this->option('orphans') || $this->option('destroy-orphans');
        $remoteBranches = $shouldFilterOrphans ? $remoteBranchResolver->resolve() : null;

        if ($shouldFilterOrphans && $remoteBranches === null) {
            $this->error('Could not fetch remote branches. Check your git credentials and repository configuration.');

            return self::FAILURE;
        }

        $rows = $this->buildTableRows($environments, $remoteBranches);

        if ($shouldFilterOrphans) {
            $rows = array_filter($rows, fn (array $row): bool => $row[2] === 'Orphan');
        }

        if ($rows === []) {
            $message = 'No review environments found.';

            if ($shouldFilterOrphans) {
                $message = 'No orphaned environments found.';
            }

            $this->info($message);

            return self::SUCCESS;
        }

        $this->table(['Branch', 'Domain', 'Status', 'Site ID'], array_values($rows));

        if (! $this->option('destroy-orphans')) {
            return self::SUCCESS;
        }

        return $this->destroyOrphans($builder, $environments, $remoteBranches ?? []);
    }

    /**
     * @param array<EnvironmentData> $environments
     * @param array<string>|null $remoteBranches
     * @return array<array{string, string, string, int}>
     */
    private function buildTableRows(array $environments, ?array $remoteBranches): array
    {
        return array_map(function (EnvironmentData $environment) use ($remoteBranches): array {
            $status = 'Active';

            if ($remoteBranches !== null && ! in_array($environment->branch, $remoteBranches, true)) {
                $status = 'Orphan';
            }

            return [$environment->branch, $environment->domain, $status, $environment->siteId];
        }, $environments);
    }

    /**
     * @param array<EnvironmentData> $environments
     * @param array<string> $remoteBranches
     */
    private function destroyOrphans(EnvironmentBuilder $builder, array $environments, array $remoteBranches): int
    {
        $orphans = array_filter(
            $environments,
            fn (EnvironmentData $environment): bool => ! in_array($environment->branch, $remoteBranches, true)
        );

        if ($orphans === []) {
            $this->info('No orphaned environments to destroy.');

            return self::SUCCESS;
        }

        $branchNames = implode(', ', array_map(fn (EnvironmentData $environment): string => $environment->branch, $orphans));

        if (! $this->confirm("Destroy " . count($orphans) . " orphaned environment(s)? [{$branchNames}]")) {
            $this->info('Operation cancelled.');

            return self::SUCCESS;
        }

        $hasErrors = false;

        foreach ($orphans as $orphan) {
            $this->info("Destroying environment: {$orphan->branch} ({$orphan->domain})");

            try {
                $fullEnvironment = $builder->find($orphan->branch);

                if (! $fullEnvironment instanceof EnvironmentData) {
                    $this->warn("  Environment not found: {$orphan->branch}");

                    continue;
                }

                $builder->destroy($fullEnvironment);
                $this->info("  Destroyed successfully.");
            } catch (Throwable $throwable) {
                $this->error("  Error: {$throwable->getMessage()}");
                $hasErrors = true;
            }
        }

        if ($hasErrors) {
            return self::FAILURE;
        }

        $this->info('All orphaned environments destroyed.');

        return self::SUCCESS;
    }
}
