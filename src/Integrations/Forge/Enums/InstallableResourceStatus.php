<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Integrations\Forge\Enums;

/**
 * Shared status values for database schemas and database users. Neither
 * resource exposes a failure state in the Forge API v2 specification.
 */
enum InstallableResourceStatus: string
{
    case INSTALLING = 'installing';
    case INSTALLED = 'installed';
    case UPDATING = 'updating';
    case REMOVING = 'removing';

    public function isInstalled(): bool
    {
        return $this === self::INSTALLED;
    }
}
