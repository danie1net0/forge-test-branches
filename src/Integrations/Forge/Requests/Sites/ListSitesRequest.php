<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Integrations\Forge\Requests\Sites;

use Ddr\ForgeTestBranches\Data\SiteData;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};

class ListSitesRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected int $serverId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/sites";
    }

    /** @return array<SiteData> */
    public function createDtoFromResponse(Response $response): array
    {
        $sites = $response->json('sites') ?? [];

        return array_map(
            fn (array $site): SiteData => SiteData::from(array_merge($site, ['server_id' => $this->serverId])),
            $sites
        );
    }
}
