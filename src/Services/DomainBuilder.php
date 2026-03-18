<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Services;

class DomainBuilder
{
    public function build(string $branchSlug): string
    {
        $pattern = (string) config('forge-test-branches.domain.pattern');
        $base = (string) config('forge-test-branches.domain.base');

        return str_replace(
            ['{branch}', '{base}'],
            [$branchSlug, $base],
            $pattern
        );
    }

    public function extractSlugFromDomain(string $domain): ?string
    {
        $pattern = (string) config('forge-test-branches.domain.pattern');
        $base = (string) config('forge-test-branches.domain.base');

        $regex = '/^' . str_replace(
            ['\{branch\}', '\{base\}'],
            ['(?P<branch>.+)', preg_quote($base, '/')],
            preg_quote($pattern, '/')
        ) . '$/';

        if (! preg_match($regex, $domain, $matches)) {
            return null;
        }

        return $matches['branch'];
    }
}
