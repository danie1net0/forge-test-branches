<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Integrations\Forge\Requests\Sites;

use Ddr\ForgeTestBranches\Data\{CreateSiteData, SiteData};
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};
use Saloon\Traits\Body\HasJsonBody;

class CreateSiteRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        protected int $serverId,
        protected CreateSiteData $data,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/sites";
    }

    public function createDtoFromResponse(Response $response): SiteData
    {
        return SiteData::from(array_merge($response->json('site'), ['server_id' => $this->serverId]));
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return $this->data->toArray();
    }
}
