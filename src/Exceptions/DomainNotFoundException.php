<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Exceptions;

class DomainNotFoundException extends ForgeTestBranchesException
{
    public static function onSite(string $domain): self
    {
        return new self("Domain not found on site: {$domain}");
    }
}
