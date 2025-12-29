<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Integrations\Forge\Resources;

use Ddr\ForgeTestBranches\Data\{CreateDatabaseUserData, DatabaseUserData};
use Ddr\ForgeTestBranches\Integrations\Forge\Requests\Databases\{CreateDatabaseUserRequest, DeleteDatabaseUserRequest};
use Ddr\ForgeTestBranches\Integrations\Forge\ForgeConnector;

class DatabaseUserResource
{
    public function __construct(
        protected ForgeConnector $connector
    ) {
    }

    public function create(int $serverId, CreateDatabaseUserData $data): DatabaseUserData
    {
        $request = new CreateDatabaseUserRequest($serverId, $data);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function delete(int $serverId, int $userId): void
    {
        $this->connector->send(new DeleteDatabaseUserRequest($serverId, $userId));
    }
}
