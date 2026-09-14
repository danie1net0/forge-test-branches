<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Integrations\Forge\Requests\Domains;

use Ddr\ForgeTestBranches\Data\DomainData;
use Ddr\ForgeTestBranches\Integrations\Forge\Requests\PaginatedRequest;

/**
 * @extends PaginatedRequest<DomainData>
 */
class ListDomainsRequest extends PaginatedRequest
{
    public function __construct(
        protected int $serverId,
        protected int $siteId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/sites/{$this->siteId}/domains";
    }

    /** @param array<string, mixed> $attributes */
    protected function createItem(array $attributes): DomainData
    {
        return DomainData::from($attributes);
    }

    /** @return array<string, int> */
    protected function parentAttributes(): array
    {
        return [
            'server_id' => $this->serverId,
            'site_id' => $this->siteId,
        ];
    }
}
