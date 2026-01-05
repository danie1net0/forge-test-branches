<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Integrations\Forge\Requests\Sites;

use Ddr\ForgeTestBranches\Data\CertificateData;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};
use Saloon\Traits\Body\HasJsonBody;

class ObtainLetsEncryptCertificateRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    /**
     * @param array<string> $domains
     */
    public function __construct(
        protected int $serverId,
        protected int $siteId,
        protected array $domains,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/sites/{$this->siteId}/certificates/letsencrypt";
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

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return [
            'domains' => $this->domains,
        ];
    }
}
