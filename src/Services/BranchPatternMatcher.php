<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Services;

class BranchPatternMatcher
{
    public function isAllowed(string $branch): bool
    {
        /** @var array<string> $patterns */
        $patterns = config('forge-test-branches.branch.patterns') ?? ['*'];

        foreach ($patterns as $pattern) {
            if ($this->matches($branch, $pattern)) {
                return true;
            }
        }

        return false;
    }

    public function matches(string $branch, string $pattern): bool
    {
        return fnmatch($pattern, $branch);
    }
}
