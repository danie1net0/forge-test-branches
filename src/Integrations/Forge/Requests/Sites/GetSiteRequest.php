<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Integrations\Forge\Requests\Sites;

use Ddr\ForgeTestBranches\Data\SiteData;
use Ddr\ForgeTestBranches\Integrations\Forge\Concerns\ParsesJsonApiResponses;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};

class GetSiteRequest extends Request
{
    use ParsesJsonApiResponses;

    protected Method $method = Method::GET;

    public function __construct(
        protected int $serverId,
        protected int $siteId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/sites/{$this->siteId}";
    }

    public function createDtoFromResponse(Response $response): SiteData
    {
        return $this->dtoFromResponse($response, SiteData::class, ['server_id' => $this->serverId]);
    }
}
