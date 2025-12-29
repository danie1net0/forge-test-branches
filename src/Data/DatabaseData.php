<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Data;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class DatabaseData extends Data
{
    public function __construct(
        public int $id,
        public int $serverId,
        public string $name,
        public string $status,
        public string $createdAt,
    ) {
    }
}
