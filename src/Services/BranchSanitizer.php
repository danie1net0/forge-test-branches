<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Services;

class BranchSanitizer
{
    public function sanitize(string $branch): string
    {
        $sanitized = (string) preg_replace('/[^a-zA-Z0-9-]/', '-', $branch);
        $sanitized = (string) preg_replace('/-+/', '-', $sanitized);
        $sanitized = mb_trim($sanitized, '-');

        return mb_strtolower(mb_substr($sanitized, 0, 63));
    }
}
