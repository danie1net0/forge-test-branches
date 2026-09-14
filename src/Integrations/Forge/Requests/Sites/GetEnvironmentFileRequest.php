<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Integrations\Forge\Requests\Sites;

use Ddr\ForgeTestBranches\Integrations\Forge\Concerns\ParsesJsonApiResponses;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};

class GetEnvironmentFileRequest extends Request
{
    use ParsesJsonApiResponses;

    protected Method $method = Method::GET;

    public function __construct(
        protected int $serverId,
        protected int $siteId
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/sites/{$this->siteId}/environment";
    }

    public function createDtoFromResponse(Response $response): string
    {
        return $this->stringAttributeFromResponse($response, 'content');
    }
}
