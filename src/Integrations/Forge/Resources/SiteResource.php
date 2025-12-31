<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Integrations\Forge\Resources;

use Ddr\ForgeTestBranches\Data\{CreateSiteData, InstallGitRepositoryData, SiteData};
use Ddr\ForgeTestBranches\Integrations\Forge\Requests\Sites\{CreateSiteRequest, DeleteSiteRequest, DeploySiteRequest, EnableQuickDeployRequest, GetEnvironmentRequest, InstallGitRepositoryRequest, ListSitesRequest, UpdateDeploymentScriptRequest, UpdateEnvironmentRequest};
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
}
