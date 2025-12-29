<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Integrations\Forge\Requests\Sites;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetEnvironmentRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected int $serverId,
        protected int $siteId
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/sites/{$this->siteId}/env";
    }
}
