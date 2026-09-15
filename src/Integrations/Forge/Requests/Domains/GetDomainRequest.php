<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Integrations\Forge\Requests\Domains;

use Ddr\ForgeTestBranches\Data\DomainData;
use Ddr\ForgeTestBranches\Integrations\Forge\Concerns\ParsesJsonApiResponses;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};

class GetDomainRequest extends Request
{
    use ParsesJsonApiResponses;

    protected Method $method = Method::GET;

    public function __construct(
        protected int $serverId,
        protected int $siteId,
        protected int $domainId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/sites/{$this->siteId}/domains/{$this->domainId}";
    }

    public function createDtoFromResponse(Response $response): DomainData
    {
        return $this->dtoFromResponse($response, DomainData::class, [
            'server_id' => $this->serverId,
            'site_id' => $this->siteId,
        ]);
    }
}
