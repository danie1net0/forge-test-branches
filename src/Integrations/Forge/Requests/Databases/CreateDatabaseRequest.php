<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Integrations\Forge\Requests\Databases;

use Ddr\ForgeTestBranches\Data\{CreateDatabaseData, DatabaseData};
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};
use Saloon\Traits\Body\HasJsonBody;

class CreateDatabaseRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected int $serverId,
        protected CreateDatabaseData $data,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/databases";
    }

    public function createDtoFromResponse(Response $response): DatabaseData
    {
        return DatabaseData::from(array_merge($response->json('database'), ['server_id' => $this->serverId]));
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return $this->data->toArray();
    }
}
