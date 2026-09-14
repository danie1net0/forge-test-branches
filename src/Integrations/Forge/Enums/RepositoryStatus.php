<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Integrations\Forge\Enums;

enum RepositoryStatus: string
{
    case INSTALLED = 'installed';
    case INSTALLING = 'installing';
    case REMOVING = 'removing';

    public function isReady(): bool
    {
        return $this === self::INSTALLED;
    }
}
