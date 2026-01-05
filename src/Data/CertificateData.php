<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Data;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class CertificateData extends Data
{
    /**
     * @param array<string>|null $domains
     */
    public function __construct(
        public int $id,
        public int $serverId,
        public int $siteId,
        public ?array $domains,
        public string $requestStatus,
        public string $status,
        public bool $existing,
        public bool $active,
        public string $createdAt,
        public ?string $activatedAt,
    ) {
    }
}
