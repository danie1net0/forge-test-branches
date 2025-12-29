<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Integrations\Forge\Resources;

use Ddr\ForgeTestBranches\Data\{CreateDatabaseData, DatabaseData};
use Ddr\ForgeTestBranches\Integrations\Forge\Requests\Databases\{CreateDatabaseRequest, DeleteDatabaseRequest};
use Ddr\ForgeTestBranches\Integrations\Forge\ForgeConnector;

class DatabaseResource
{
    public function __construct(
        protected ForgeConnector $connector
    ) {
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
