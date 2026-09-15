<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Integrations\Forge\Enums;

/**
 * Union of the status values database schemas (installing, installed,
 * removing) and database users (installing, installed, updating, removing)
 * accept in the Forge API v2 specification — the two resources don't share
 * an identical set, but every value either can return is covered here.
 * Neither resource exposes a failure state.
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
