<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Data;

use Spatie\LaravelData\Data;

class EnvironmentData extends Data
{
    public function __construct(
        public string $branch,
        public string $slug,
        public string $domain,
        public int $serverId,
        public int $siteId,
        public ?int $databaseId = null,
        public ?int $databaseUserId = null,
    ) {
    }
}
