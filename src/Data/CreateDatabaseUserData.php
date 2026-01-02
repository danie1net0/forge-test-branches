<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Data;

use Spatie\LaravelData\Attributes\{MapInputName, MapOutputName};
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(SnakeCaseMapper::class)]
class CreateDatabaseUserData extends Data
{
    public function __construct(
        public string $name,
        public string $password,
        /** @var array<int> */
        public array $databases = [],
    ) {
    }
}
