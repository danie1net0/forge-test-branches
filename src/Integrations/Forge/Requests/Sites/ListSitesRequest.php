<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Integrations\Forge\Requests\Sites;

use Ddr\ForgeTestBranches\Data\SiteData;
use Ddr\ForgeTestBranches\Integrations\Forge\Requests\Concerns\FiltersByName;
use Ddr\ForgeTestBranches\Integrations\Forge\Requests\PaginatedRequest;

/**
 * @extends PaginatedRequest<SiteData>
 */
class ListSitesRequest extends PaginatedRequest
{
    use FiltersByName;

    public function __construct(
        protected int $serverId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/sites";
    }

    /** @param array<string, mixed> $attributes */
    protected function createItem(array $attributes): SiteData
    {
        return SiteData::from($attributes);
    }

    /** @return array<string, int> */
    protected function parentAttributes(): array
    {
        return ['server_id' => $this->serverId];
    }
}
