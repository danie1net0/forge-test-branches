<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Integrations\Forge\Requests\Databases;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};
use Ddr\ForgeTestBranches\Data\{CreateDatabaseData, DatabaseData};
use Saloon\Repositories\Body\JsonBodyRepository;

class CreateDatabaseRequest extends Request implements HasBody
{
    protected Method $method = Method::POST;

    protected JsonBodyRepository $body;

    public function __construct(
        protected int $serverId,
        protected CreateDatabaseData $data,
    ) {
        $this->body = new JsonBodyRepository($this->data->toArray());
    }

    public function body(): JsonBodyRepository
    {
        return $this->body;
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/databases";
    }

    public function createDtoFromResponse(Response $response): DatabaseData
    {
        return DatabaseData::from(array_merge($response->json('database'), ['server_id' => $this->serverId]));
    }
}
