<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Data;

use Ddr\ForgeTestBranches\Integrations\Forge\Enums\CertificateStatus;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class CertificateData extends Data
{
    public function __construct(
        public int $id,
        public int $serverId,
        public int $siteId,
        public int $domainId,
        public string $type,
        public string $requestStatus,
        public string $status,
        public ?bool $active = null,
        public ?string $createdAt = null,
    ) {
    }

    public function isReady(): bool
    {
        return $this->status === CertificateStatus::INSTALLED->value && $this->active === true;
    }

    public function hasFailed(): bool
    {
        return CertificateStatus::tryFrom($this->status)?->hasFailed() ?? false;
    }
}
