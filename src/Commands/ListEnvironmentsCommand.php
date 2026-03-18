<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Commands;

use Ddr\ForgeTestBranches\Data\EnvironmentData;
use Ddr\ForgeTestBranches\Services\EnvironmentBuilder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Throwable;

class ListEnvironmentsCommand extends Command
{
    protected $signature = 'forge-test-branches:list
        {--orphans : Show only orphaned environments (branch no longer exists on remote)}
        {--destroy-orphans : Destroy all orphaned environments}';

    protected $description = 'Lists review environments on the server';

    public function handle(EnvironmentBuilder $builder): int
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
        $remoteBranches = $shouldFilterOrphans ? $this->getRemoteBranches() : null;

        if ($shouldFilterOrphans && $remoteBranches === null) {
            $this->error('Could not fetch remote branches. Check your git credentials and repository configuration.');

            return self::FAILURE;
        }

        $rows = $this->buildTableRows($environments, $remoteBranches);

        if ($shouldFilterOrphans) {
            $rows = array_filter($rows, fn (array $row): bool => $row[2] === 'Orphan');
        }

        if ($rows === []) {
            $this->info($shouldFilterOrphans ? 'No orphaned environments found.' : 'No review environments found.');

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

    /** @return array<string>|null */
    private function getRemoteBranches(): ?array
    {
        $provider = (string) config('forge-test-branches.git.provider');
        $repository = (string) config('forge-test-branches.git.repository');

        $host = match ($provider) {
            'github' => 'github.com',
            'gitlab' => 'gitlab.com',
            'bitbucket' => 'bitbucket.org',
            default => null,
        };

        if ($host === null) {
            $this->warn("Unsupported git provider: {$provider}");

            return null;
        }

        $result = Process::run("git ls-remote --heads https://{$host}/{$repository}.git 2>/dev/null");

        if (! $result->successful()) {
            $result = Process::run("git ls-remote --heads git@{$host}:{$repository}.git 2>/dev/null");
        }

        if (! $result->successful()) {
            return null;
        }

        $branches = [];

        foreach (explode("\n", mb_trim($result->output())) as $line) {
            if ($line === '') {
                continue;
            }

            $parts = explode("\t", $line);

            if (count($parts) !== 2) {
                continue;
            }

            $branches[] = str_replace('refs/heads/', '', $parts[1]);
        }

        return $branches;
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
