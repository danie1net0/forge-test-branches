<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Integrations\Forge\Requests\Sites;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Repositories\Body\JsonBodyRepository;

class UpdateDeploymentScriptRequest extends Request implements HasBody
{
    protected Method $method = Method::PUT;

    protected JsonBodyRepository $body;

    public function __construct(
        protected int $serverId,
        protected int $siteId,
        protected string $content
    ) {
        $this->body = new JsonBodyRepository(['content' => $this->content]);
    }

    public function body(): JsonBodyRepository
    {
        return $this->body;
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/sites/{$this->siteId}/deployment/script";
    }
}
