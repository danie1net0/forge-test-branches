<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Integrations\Forge\Requests\Sites;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class DeleteSiteRequest extends Request
{
    protected Method $method = Method::DELETE;

    public function __construct(
        protected int $serverId,
        protected int $siteId
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/sites/{$this->siteId}";
    }
}
