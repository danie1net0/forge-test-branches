<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Integrations\Forge\Requests\Databases;

use Ddr\ForgeTestBranches\Data\DatabaseUserData;
use Ddr\ForgeTestBranches\Integrations\Forge\Concerns\ParsesJsonApiResponses;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};

class GetDatabaseUserRequest extends Request
{
    use ParsesJsonApiResponses;

    protected Method $method = Method::GET;

    public function __construct(
        protected int $serverId,
        protected int $databaseUserId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/database/users/{$this->databaseUserId}";
    }

    public function createDtoFromResponse(Response $response): DatabaseUserData
    {
        return $this->dtoFromResponse($response, DatabaseUserData::class, ['server_id' => $this->serverId]);
    }
}
