<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Integrations\Forge\Requests\Sites;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class UpdateEnvironmentFileRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::PUT;

    public function __construct(
        protected int $serverId,
        protected int $siteId,
        protected string $content
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/sites/{$this->siteId}/environment";
    }

    /**
     * @return array<string, string>
     */
    protected function defaultBody(): array
    {
        return ['environment' => $this->content];
    }
}
