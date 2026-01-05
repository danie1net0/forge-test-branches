<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Integrations\Forge\Resources;

use Illuminate\Support\Sleep;
use Ddr\ForgeTestBranches\Data\{CertificateData, CreateSiteData, InstallGitRepositoryData, SiteData};
use Ddr\ForgeTestBranches\Integrations\Forge\Requests\Sites\{CreateSiteRequest, DeleteSiteRequest, DeploySiteRequest, EnableQuickDeployRequest, GetCertificateRequest, GetEnvironmentRequest, GetSiteRequest, InstallGitRepositoryRequest, ListSitesRequest, ObtainLetsEncryptCertificateRequest, UpdateDeploymentScriptRequest, UpdateEnvironmentRequest};
use RuntimeException;
use Ddr\ForgeTestBranches\Integrations\Forge\ForgeConnector;

class SiteResource
{
    public function __construct(
        protected ForgeConnector $connector
    ) {
    }

    /** @return array<SiteData> */
    public function list(int $serverId): array
    {
        $request = new ListSitesRequest($serverId);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function get(int $serverId, int $siteId): SiteData
    {
        $request = new GetSiteRequest($serverId, $siteId);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function findByDomain(int $serverId, string $domain): ?SiteData
    {
        $sites = $this->list($serverId);

        foreach ($sites as $site) {
            if ($site->name === $domain) {
                return $site;
            }
        }

        return null;
    }

    public function waitForRepositoryInstallation(int $serverId, int $siteId, int $maxAttempts = 30, int $sleepSeconds = 5): SiteData
    {
        for ($i = 0; $i < $maxAttempts; $i++) {
            $site = $this->get($serverId, $siteId);

            if ($site->repositoryStatus === 'installed') {
                return $site;
            }

            Sleep::sleep($sleepSeconds);
        }

        throw new RuntimeException('Timeout waiting for repository installation');
    }

    public function create(int $serverId, CreateSiteData $data): SiteData
    {
        $request = new CreateSiteRequest($serverId, $data);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function delete(int $serverId, int $siteId): void
    {
        $this->connector->send(new DeleteSiteRequest($serverId, $siteId));
    }

    public function deploy(int $serverId, int $siteId): void
    {
        $this->connector->send(new DeploySiteRequest($serverId, $siteId));
    }

    public function installGitRepository(int $serverId, int $siteId, InstallGitRepositoryData $data): SiteData
    {
        $request = new InstallGitRepositoryRequest($serverId, $siteId, $data);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function updateDeploymentScript(int $serverId, int $siteId, string $content): void
    {
        $this->connector->send(new UpdateDeploymentScriptRequest($serverId, $siteId, $content));
    }

    public function enableQuickDeploy(int $serverId, int $siteId): void
    {
        $this->connector->send(new EnableQuickDeployRequest($serverId, $siteId));
    }

    public function getEnvironment(int $serverId, int $siteId): string
    {
        $response = $this->connector->send(new GetEnvironmentRequest($serverId, $siteId));

        return $response->body();
    }

    public function updateEnvironment(int $serverId, int $siteId, string $content): void
    {
        $this->connector->send(new UpdateEnvironmentRequest($serverId, $siteId, $content));
    }

    /**
     * @param array<string> $domains
     */
    public function obtainLetsEncryptCertificate(int $serverId, int $siteId, array $domains): CertificateData
    {
        $request = new ObtainLetsEncryptCertificateRequest($serverId, $siteId, $domains);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function getCertificate(int $serverId, int $siteId, int $certificateId): CertificateData
    {
        $request = new GetCertificateRequest($serverId, $siteId, $certificateId);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function waitForCertificateActivation(int $serverId, int $siteId, int $certificateId, int $maxAttempts = 60, int $sleepSeconds = 5): CertificateData
    {
        for ($i = 0; $i < $maxAttempts; $i++) {
            $certificate = $this->getCertificate($serverId, $siteId, $certificateId);

            if ($certificate->status === 'installed' && $certificate->active) {
                return $certificate;
            }

            Sleep::sleep($sleepSeconds);
        }

        throw new RuntimeException('Timeout waiting for SSL certificate activation');
    }
}
