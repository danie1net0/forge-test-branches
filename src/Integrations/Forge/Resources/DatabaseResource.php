<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Integrations\Forge\Resources;

use Ddr\ForgeTestBranches\Data\{CreateDatabaseData, DatabaseData};
use Ddr\ForgeTestBranches\Integrations\Forge\Requests\Databases\{CreateDatabaseRequest, DeleteDatabaseRequest, ListDatabasesRequest};
use Ddr\ForgeTestBranches\Integrations\Forge\ForgeConnector;

class DatabaseResource
{
    public function __construct(
        protected ForgeConnector $connector
    ) {
    }

    /** @return array<DatabaseData> */
    public function list(int $serverId): array
    {
        $request = new ListDatabasesRequest($serverId);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function findByName(int $serverId, string $name): ?DatabaseData
    {
        $databases = $this->list($serverId);

        foreach ($databases as $database) {
            if ($database->name === $name) {
                return $database;
            }
        }

        return null;
    }

    public function create(int $serverId, CreateDatabaseData $data): DatabaseData
    {
        $request = new CreateDatabaseRequest($serverId, $data);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function delete(int $serverId, int $databaseId): void
    {
        $this->connector->send(new DeleteDatabaseRequest($serverId, $databaseId));
    }
}
