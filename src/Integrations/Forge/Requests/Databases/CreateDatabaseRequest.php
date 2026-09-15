<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Integrations\Forge\Requests\Databases;

use Ddr\ForgeTestBranches\Data\{CreateDatabaseData, DatabaseData};
use Ddr\ForgeTestBranches\Integrations\Forge\Concerns\ParsesJsonApiResponses;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};
use Saloon\Traits\Body\HasJsonBody;

class CreateDatabaseRequest extends Request implements HasBody
{
    use HasJsonBody;
    use ParsesJsonApiResponses;

    protected Method $method = Method::POST;

    public function __construct(
        protected int $serverId,
        protected CreateDatabaseData $data,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/database/schemas";
    }

    public function createDtoFromResponse(Response $response): DatabaseData
    {
        return $this->dtoFromResponse($response, DatabaseData::class, ['server_id' => $this->serverId]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return $this->data->toArray();
    }
}
