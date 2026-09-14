<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Data;

use Ddr\ForgeTestBranches\Data\Contracts\HasNameAttribute;
use Ddr\ForgeTestBranches\Integrations\Forge\Enums\InstallableResourceStatus;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class DatabaseData extends Data implements HasNameAttribute
{
    public function __construct(
        public int $id,
        public int $serverId,
        public string $name,
        public string $status,
        public string $createdAt,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isInstalled(): bool
    {
        return InstallableResourceStatus::tryFrom($this->status)?->isInstalled() ?? false;
    }
}
