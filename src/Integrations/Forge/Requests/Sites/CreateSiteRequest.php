<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Integrations\Forge\Requests\Sites;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};
use Ddr\ForgeTestBranches\Data\{CreateSiteData, SiteData};
use Saloon\Repositories\Body\JsonBodyRepository;

class CreateSiteRequest extends Request implements HasBody
{
    protected Method $method = Method::POST;

    protected JsonBodyRepository $body;

    public function __construct(
        protected int $serverId,
        protected CreateSiteData $data,
    ) {
        $this->body = new JsonBodyRepository($this->data->toArray());
    }

    public function body(): JsonBodyRepository
    {
        return $this->body;
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/sites";
    }

    public function createDtoFromResponse(Response $response): SiteData
    {
        return SiteData::from(array_merge($response->json('site'), ['server_id' => $this->serverId]));
    }
}
