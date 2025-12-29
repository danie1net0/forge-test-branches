<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Services;

class DomainBuilder
{
    public function build(string $branchSlug): string
    {
        $pattern = config('forge-test-branches.domain.pattern');
        $base = config('forge-test-branches.domain.base');

        return str_replace(
            ['{branch}', '{base}'],
            [$branchSlug, $base],
            $pattern
        );
    }
}
