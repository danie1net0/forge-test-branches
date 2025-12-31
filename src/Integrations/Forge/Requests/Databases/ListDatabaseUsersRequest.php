<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Integrations\Forge\Requests\Databases;

use Ddr\ForgeTestBranches\Data\DatabaseUserData;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};

class ListDatabaseUsersRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected int $serverId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/database-users";
    }

    /** @return array<DatabaseUserData> */
    public function createDtoFromResponse(Response $response): array
    {
        $users = $response->json('users') ?? [];

        return array_map(
            fn (array $user): DatabaseUserData => DatabaseUserData::from(array_merge($user, ['server_id' => $this->serverId])),
            $users
        );
    }
}
