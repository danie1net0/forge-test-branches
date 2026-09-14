<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Integrations\Forge\Enums;

enum SiteStatus: string
{
    case INSTALLED = 'installed';
    case CREATING = 'creating';
    case REMOVING = 'removing';
    case INSTALLING = 'installing';
    case UNINSTALLING = 'uninstalling';
    case DEPLOYED = 'deployed';
    case NEVER_DEPLOYED = 'never-deployed';
    case DEPLOYING = 'deploying';
    case FAILED = 'failed';
    case MAINTENANCE = 'maintenance';

    /**
     * DEPLOYING is deliberately excluded: it reflects a deploy in progress
     * (including Forge's own initial deploy right after site creation),
     * not whether the site record and its repository finished installing.
     * A slow first deploy should not block the environment from being
     * considered installed.
     *
     * @var array<int, self>
     */
    private const array PENDING_STATUSES = [self::CREATING, self::INSTALLING];

    /** @var array<int, self> */
    private const array FAILURE_STATUSES = [self::FAILED, self::REMOVING, self::UNINSTALLING];

    public function isPending(): bool
    {
        return in_array($this, self::PENDING_STATUSES, true);
    }

    public function hasFailed(): bool
    {
        return in_array($this, self::FAILURE_STATUSES, true);
    }
}
