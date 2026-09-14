<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Exceptions;

class ResourceTimeoutException extends ForgeTestBranchesException
{
    public static function afterAttempts(string $resourceLabel, int $maxAttempts, string $lastKnownState): self
    {
        return new self("Timeout waiting for {$resourceLabel} after {$maxAttempts} attempts (last known state: {$lastKnownState}).");
    }
}
