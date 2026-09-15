<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Integrations\Forge\Requests\Domains;

use Ddr\ForgeTestBranches\Data\CertificateData;
use Ddr\ForgeTestBranches\Integrations\Forge\Concerns\ParsesJsonApiResponses;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\{Request, Response};
use Saloon\Traits\Body\HasJsonBody;

class ObtainLetsEncryptCertificateRequest extends Request implements HasBody
{
    use HasJsonBody;
    use ParsesJsonApiResponses;

    protected Method $method = Method::POST;

    public function __construct(
        protected int $serverId,
        protected int $siteId,
        protected int $domainId,
        protected string $verificationMethod = 'http-01',
    ) {
    }

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/sites/{$this->siteId}/domains/{$this->domainId}/certificates";
    }

    public function createDtoFromResponse(Response $response): CertificateData
    {
        return $this->dtoFromResponse($response, CertificateData::class, [
            'server_id' => $this->serverId,
            'site_id' => $this->siteId,
            'domain_id' => $this->domainId,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return [
            'type' => 'letsencrypt',
            // With `true` Forge switches nginx to the certificate before Let's Encrypt issues it, and validation fails.
            'enable' => false,
            'letsencrypt' => [
                'verification_method' => $this->verificationMethod,
                'key_type' => 'ecdsa',
            ],
        ];
    }
}
