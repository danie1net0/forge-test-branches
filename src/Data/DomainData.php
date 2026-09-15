<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Data;

use Ddr\ForgeTestBranches\Data\Contracts\HasNameAttribute;
use Ddr\ForgeTestBranches\Integrations\Forge\Enums\DomainRecordStatus;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class DomainData extends Data implements HasNameAttribute
{
    public function __construct(
        public int $id,
        public int $serverId,
        public int $siteId,
        public string $name,
        public string $type,
        public string $status,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isEnabled(): bool
    {
        return DomainRecordStatus::tryFrom($this->status)?->isEnabled() ?? false;
    }
}
