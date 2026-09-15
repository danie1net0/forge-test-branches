<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Integrations\Forge\Resources;

use Ddr\ForgeTestBranches\Data\{CreateDatabaseUserData, DatabaseUserData};
use Ddr\ForgeTestBranches\Integrations\Forge\Concerns\{FindsResourceByName, WaitsForResources};
use Ddr\ForgeTestBranches\Integrations\Forge\ForgeConnector;
use Ddr\ForgeTestBranches\Integrations\Forge\Requests\Databases\{CreateDatabaseUserRequest, DeleteDatabaseUserRequest, GetDatabaseUserRequest, ListDatabaseUsersRequest};

class DatabaseUserResource
{
    use FindsResourceByName;
    use WaitsForResources;

    private const int DEFAULT_MAX_ATTEMPTS = 30;

    private const int DEFAULT_SLEEP_SECONDS = 5;

    public function __construct(
        protected ForgeConnector $connector
    ) {
    }

    /** @return array<int, DatabaseUserData> */
    public function list(int $serverId): array
    {
        return $this->connector->sendPaginated(new ListDatabaseUsersRequest($serverId));
    }

    public function get(int $serverId, int $databaseUserId): DatabaseUserData
    {
        $request = new GetDatabaseUserRequest($serverId, $databaseUserId);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function findByName(int $serverId, string $name): ?DatabaseUserData
    {
        $users = $this->connector->sendPaginated(new ListDatabaseUsersRequest($serverId)->filterByName($name));

        return $this->firstMatchingName($users, $name);
    }

    public function create(int $serverId, CreateDatabaseUserData $data): DatabaseUserData
    {
        $request = new CreateDatabaseUserRequest($serverId, $data);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function waitForInstallation(int $serverId, int $databaseUserId, int $maxAttempts = self::DEFAULT_MAX_ATTEMPTS, int $sleepSeconds = self::DEFAULT_SLEEP_SECONDS): DatabaseUserData
    {
        return $this->waitUntil(
            fetchResource: fn (): DatabaseUserData => $this->get($serverId, $databaseUserId),
            isReady: fn (DatabaseUserData $databaseUser): bool => $databaseUser->isInstalled(),
            maxAttempts: $maxAttempts,
            sleepSeconds: $sleepSeconds,
            resourceLabel: "database user installation (database user {$databaseUserId})",
            describe: fn (DatabaseUserData $databaseUser): string => "status={$databaseUser->status}",
        );
    }

    public function delete(int $serverId, int $databaseUserId): void
    {
        $this->connector->send(new DeleteDatabaseUserRequest($serverId, $databaseUserId));
    }
}
