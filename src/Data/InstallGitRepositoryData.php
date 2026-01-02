<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Data;

use Spatie\LaravelData\Attributes\{MapInputName, MapOutputName};
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(SnakeCaseMapper::class)]
class InstallGitRepositoryData extends Data
{
    public function __construct(
        public string $provider,
        public string $repository,
        public ?string $branch = null,
        public ?bool $composer = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return array_filter(parent::toArray(), fn ($value): bool => $value !== null);
    }
}
