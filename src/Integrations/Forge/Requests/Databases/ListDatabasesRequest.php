<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Integrations\Forge\Requests\Databases;

use Ddr\ForgeTestBranches\Data\DatabaseData;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};

class ListDatabasesRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected int $serverId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/databases";
    }

    /** @return array<DatabaseData> */
    public function createDtoFromResponse(Response $response): array
    {
        $databases = $response->json('databases') ?? [];

        return array_map(
            fn (array $database): DatabaseData => DatabaseData::from(array_merge($database, ['server_id' => $this->serverId])),
            $databases
        );
    }
}
