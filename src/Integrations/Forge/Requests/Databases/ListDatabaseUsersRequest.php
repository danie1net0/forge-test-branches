<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Integrations\Forge\Requests\Databases;

use Ddr\ForgeTestBranches\Data\DatabaseUserData;
use Ddr\ForgeTestBranches\Integrations\Forge\Requests\Concerns\FiltersByName;
use Ddr\ForgeTestBranches\Integrations\Forge\Requests\PaginatedRequest;

/**
 * @extends PaginatedRequest<DatabaseUserData>
 */
class ListDatabaseUsersRequest extends PaginatedRequest
{
    use FiltersByName;

    public function __construct(
        protected int $serverId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/database/users";
    }

    /** @param array<string, mixed> $attributes */
    protected function createItem(array $attributes): DatabaseUserData
    {
        return DatabaseUserData::from($attributes);
    }

    /** @return array<string, int> */
    protected function parentAttributes(): array
    {
        return ['server_id' => $this->serverId];
    }
}
