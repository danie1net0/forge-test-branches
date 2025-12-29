<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Data;

use Spatie\LaravelData\Attributes\{MapInputName, MapOutputName};
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(SnakeCaseMapper::class)]
class CreateSiteData extends Data
{
    /** @param array<string>|null $aliases */
    public function __construct(
        public string $domain,
        public string $projectType,
        public ?array $aliases = null,
        public ?string $directory = null,
        public ?bool $isolated = null,
        public ?string $username = null,
        public ?string $database = null,
        public ?string $phpVersion = null,
        public ?int $nginxTemplate = null,
    ) {
    }
}
