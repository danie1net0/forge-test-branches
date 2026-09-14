<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Integrations\Forge\Resources;

use Ddr\ForgeTestBranches\Data\{CreateDatabaseData, DatabaseData};
use Ddr\ForgeTestBranches\Integrations\Forge\Concerns\{FindsResourceByName, WaitsForResources};
use Ddr\ForgeTestBranches\Integrations\Forge\ForgeConnector;
use Ddr\ForgeTestBranches\Integrations\Forge\Requests\Databases\{CreateDatabaseRequest, DeleteDatabaseRequest, GetDatabaseRequest, ListDatabasesRequest};

class DatabaseResource
{
    use FindsResourceByName;
    use WaitsForResources;

    private const int DEFAULT_MAX_ATTEMPTS = 30;

    private const int DEFAULT_SLEEP_SECONDS = 5;

    public function __construct(
        protected ForgeConnector $connector
    ) {
    }

    /** @return array<int, DatabaseData> */
    public function list(int $serverId): array
    {
        return $this->connector->sendPaginated(new ListDatabasesRequest($serverId));
    }

    public function get(int $serverId, int $databaseId): DatabaseData
    {
        $request = new GetDatabaseRequest($serverId, $databaseId);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function findByName(int $serverId, string $name): ?DatabaseData
    {
        $databases = $this->connector->sendPaginated(new ListDatabasesRequest($serverId)->filterByName($name));

        return $this->firstMatchingName($databases, $name);
    }

    public function create(int $serverId, CreateDatabaseData $data): DatabaseData
    {
        $request = new CreateDatabaseRequest($serverId, $data);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function waitForInstallation(int $serverId, int $databaseId, int $maxAttempts = self::DEFAULT_MAX_ATTEMPTS, int $sleepSeconds = self::DEFAULT_SLEEP_SECONDS): DatabaseData
    {
        return $this->waitUntil(
            fetchResource: fn (): DatabaseData => $this->get($serverId, $databaseId),
            isReady: fn (DatabaseData $database): bool => $database->isInstalled(),
            maxAttempts: $maxAttempts,
            sleepSeconds: $sleepSeconds,
            resourceLabel: "database installation (database {$databaseId})",
            describe: fn (DatabaseData $database): string => "status={$database->status}",
        );
    }

    public function delete(int $serverId, int $databaseId): void
    {
        $this->connector->send(new DeleteDatabaseRequest($serverId, $databaseId));
    }
}
