<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Exceptions;

class ResourceFailedException extends ForgeTestBranchesException
{
    public static function withReason(string $resourceLabel, string $reason): self
    {
        return new self("{$resourceLabel} failed: {$reason}");
    }
}
