<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Services;

use Illuminate\Support\Facades\Process;

class RemoteBranchResolver
{
    /** @return array<string>|null */
    public function resolve(): ?array
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
            return null;
        }

        $httpsUrl = escapeshellarg("https://{$host}/{$repository}.git");
        $sshUrl = escapeshellarg("git@{$host}:{$repository}.git");

        $result = Process::run("git ls-remote --heads {$httpsUrl}");

        if (! $result->successful()) {
            $result = Process::run("git ls-remote --heads {$sshUrl}");
        }

        if (! $result->successful()) {
            return null;
        }

        return $this->parseOutput($result->output());
    }

    /** @return array<string> */
    private function parseOutput(string $output): array
    {
        $branches = [];

        foreach (explode("\n", mb_trim($output)) as $line) {
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
}
