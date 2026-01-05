<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Integrations\Forge\Requests\Sites;

use Ddr\ForgeTestBranches\Data\CertificateData;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};

class GetCertificateRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected int $serverId,
        protected int $siteId,
        protected int $certificateId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/sites/{$this->siteId}/certificates/{$this->certificateId}";
    }

    public function createDtoFromResponse(Response $response): CertificateData
    {
        return CertificateData::from(array_merge(
            $response->json('certificate'),
            [
                'server_id' => $this->serverId,
                'site_id' => $this->siteId,
            ]
        ));
    }
}
