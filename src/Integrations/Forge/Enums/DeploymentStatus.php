<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Integrations\Forge\Enums;

enum DeploymentStatus: string
{
    case PENDING = 'pending';
    case QUEUED = 'queued';
    case DEPLOYING = 'deploying';
    case FINISHED = 'finished';
    case CANCELLED = 'cancelled';
    case FAILED = 'failed';
    case FAILED_BUILD = 'failed-build';

    /** @var array<int, self> */
    private const array PENDING_STATUSES = [self::PENDING, self::QUEUED, self::DEPLOYING];

    /** @var array<int, self> */
    private const array FAILURE_STATUSES = [self::FAILED, self::FAILED_BUILD, self::CANCELLED];

    public function isPending(): bool
    {
        return in_array($this, self::PENDING_STATUSES, true);
    }

    public function hasFailed(): bool
    {
        return in_array($this, self::FAILURE_STATUSES, true);
    }
}
