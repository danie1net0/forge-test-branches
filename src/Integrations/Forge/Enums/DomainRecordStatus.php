<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Integrations\Forge\Enums;

enum DomainRecordStatus: string
{
    case PENDING = 'pending';
    case CONNECTING = 'connecting';
    case ENABLED = 'enabled';
    case REMOVING = 'removing';
    case SECURING = 'securing';
    case UPDATING = 'updating';
    case DISABLING = 'disabling';
    case DISABLED = 'disabled';
    case ENABLING = 'enabling';

    public function isEnabled(): bool
    {
        return $this === self::ENABLED;
    }
}
