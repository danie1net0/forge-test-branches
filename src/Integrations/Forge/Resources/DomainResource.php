<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Integrations\Forge\Resources;

use Ddr\ForgeTestBranches\Data\{CertificateData, DomainData};
use Ddr\ForgeTestBranches\Integrations\Forge\Concerns\{FindsResourceByName, WaitsForResources};
use Ddr\ForgeTestBranches\Integrations\Forge\ForgeConnector;
use Ddr\ForgeTestBranches\Integrations\Forge\Requests\Domains\{GetCertificateRequest, GetDomainRequest, ListDomainsRequest, ObtainLetsEncryptCertificateRequest};

class DomainResource
{
    use FindsResourceByName;
    use WaitsForResources;

    private const int DEFAULT_MAX_ATTEMPTS = 60;

    private const int DEFAULT_SLEEP_SECONDS = 5;

    public function __construct(
        protected ForgeConnector $connector
    ) {
    }

    /** @return array<int, DomainData> */
    public function list(int $serverId, int $siteId): array
    {
        return $this->connector->sendPaginated(new ListDomainsRequest($serverId, $siteId));
    }

    public function findByName(int $serverId, int $siteId, string $name): ?DomainData
    {
        return $this->firstMatchingName($this->list($serverId, $siteId), $name);
    }

    public function get(int $serverId, int $siteId, int $domainId): DomainData
    {
        $request = new GetDomainRequest($serverId, $siteId, $domainId);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function waitForEnabled(int $serverId, int $siteId, int $domainId, int $maxAttempts = self::DEFAULT_MAX_ATTEMPTS, int $sleepSeconds = self::DEFAULT_SLEEP_SECONDS): DomainData
    {
        return $this->waitUntil(
            fetchResource: fn (): DomainData => $this->get($serverId, $siteId, $domainId),
            isReady: fn (DomainData $domain): bool => $domain->isEnabled(),
            maxAttempts: $maxAttempts,
            sleepSeconds: $sleepSeconds,
            resourceLabel: "domain activation (domain {$domainId})",
            describe: fn (DomainData $domain): string => "status={$domain->status}",
        );
    }

    public function obtainLetsEncryptCertificate(int $serverId, int $siteId, int $domainId, string $verificationMethod = 'http-01', string $keyType = 'ecdsa'): CertificateData
    {
        $request = new ObtainLetsEncryptCertificateRequest($serverId, $siteId, $domainId, $verificationMethod, $keyType);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function getCertificate(int $serverId, int $siteId, int $domainId, int $certificateId): CertificateData
    {
        $request = new GetCertificateRequest($serverId, $siteId, $domainId, $certificateId);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function waitForCertificateActivation(int $serverId, int $siteId, int $domainId, int $certificateId, int $maxAttempts = self::DEFAULT_MAX_ATTEMPTS, int $sleepSeconds = self::DEFAULT_SLEEP_SECONDS): CertificateData
    {
        return $this->waitUntil(
            fetchResource: fn (): CertificateData => $this->getCertificate($serverId, $siteId, $domainId, $certificateId),
            isReady: fn (CertificateData $certificate): bool => $certificate->isReady(),
            maxAttempts: $maxAttempts,
            sleepSeconds: $sleepSeconds,
            resourceLabel: "SSL certificate activation (certificate {$certificateId})",
            hasFailed: fn (CertificateData $certificate): bool => $certificate->hasFailed(),
            describe: fn (CertificateData $certificate): string => "status={$certificate->status}",
        );
    }
}
