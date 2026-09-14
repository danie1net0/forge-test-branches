<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Integrations\Forge\Enums;

enum CertificateStatus: string
{
    case INSTALLING = 'installing';
    case INSTALLED = 'installed';
    case REMOVING = 'removing';
    case RENEWING = 'renewing';
    case FAILED = 'failed';
    case FAILED_UNKNOWN = 'failed-unknown';
    case FAILED_RUNNER = 'failed-runner';

    /** @var array<int, self> */
    private const array FAILURE_STATUSES = [self::FAILED, self::FAILED_UNKNOWN, self::FAILED_RUNNER];

    public function hasFailed(): bool
    {
        return in_array($this, self::FAILURE_STATUSES, true);
    }
}
