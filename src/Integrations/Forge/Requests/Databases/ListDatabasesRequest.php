<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Integrations\Forge\Requests\Databases;

use Ddr\ForgeTestBranches\Data\DatabaseData;
use Ddr\ForgeTestBranches\Integrations\Forge\Requests\Concerns\FiltersByName;
use Ddr\ForgeTestBranches\Integrations\Forge\Requests\PaginatedRequest;

/**
 * @extends PaginatedRequest<DatabaseData>
 */
class ListDatabasesRequest extends PaginatedRequest
{
    use FiltersByName;

    public function __construct(
        protected int $serverId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/database/schemas";
    }

    /** @param array<string, mixed> $attributes */
    protected function createItem(array $attributes): DatabaseData
    {
        return DatabaseData::from($attributes);
    }

    /** @return array<string, int> */
    protected function parentAttributes(): array
    {
        return ['server_id' => $this->serverId];
    }
}
