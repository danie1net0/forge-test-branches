<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Integrations\Forge\Requests\Domains;

use Ddr\ForgeTestBranches\Data\CertificateData;
use Ddr\ForgeTestBranches\Integrations\Forge\Concerns\ParsesJsonApiResponses;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};

class GetCertificateRequest extends Request
{
    use ParsesJsonApiResponses;

    protected Method $method = Method::GET;

    public function __construct(
        protected int $serverId,
        protected int $siteId,
        protected int $domainId,
        protected int $certificateId,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/sites/{$this->siteId}/domains/{$this->domainId}/certificates/{$this->certificateId}";
    }

    public function createDtoFromResponse(Response $response): CertificateData
    {
        return $this->dtoFromResponse($response, CertificateData::class, [
            'server_id' => $this->serverId,
            'site_id' => $this->siteId,
            'domain_id' => $this->domainId,
        ]);
    }
}
