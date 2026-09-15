<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Integrations\Forge\Resources;

use Ddr\ForgeTestBranches\Data\{CreateSiteData, SiteData};
use Ddr\ForgeTestBranches\Integrations\Forge\Concerns\{FindsResourceByName, WaitsForResources};
use Ddr\ForgeTestBranches\Integrations\Forge\ForgeConnector;
use Ddr\ForgeTestBranches\Integrations\Forge\Requests\Sites\{CreateSiteRequest, DeleteSiteRequest, DeploySiteRequest, EnableQuickDeployRequest, GetEnvironmentFileRequest, GetSiteRequest, ListSitesRequest, UpdateDeploymentScriptRequest, UpdateEnvironmentFileRequest};

class SiteResource
{
    use FindsResourceByName;
    use WaitsForResources;

    private const int DEFAULT_MAX_ATTEMPTS = 60;

    private const int DEFAULT_SLEEP_SECONDS = 5;

    private const int DEFAULT_ENVIRONMENT_MAX_ATTEMPTS = 10;

    private const int DEFAULT_ENVIRONMENT_SLEEP_SECONDS = 2;

    public function __construct(
        protected ForgeConnector $connector
    ) {
    }

    /** @return array<int, SiteData> */
    public function list(int $serverId): array
    {
        return $this->connector->sendPaginated(new ListSitesRequest($serverId));
    }

    public function get(int $serverId, int $siteId): SiteData
    {
        $request = new GetSiteRequest($serverId, $siteId);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function findByName(int $serverId, string $name): ?SiteData
    {
        $sites = $this->connector->sendPaginated(new ListSitesRequest($serverId)->filterByName($name));

        return $this->firstMatchingName($sites, $name);
    }

    public function waitForInstallation(int $serverId, int $siteId, int $maxAttempts = self::DEFAULT_MAX_ATTEMPTS, int $sleepSeconds = self::DEFAULT_SLEEP_SECONDS): SiteData
    {
        return $this->waitUntil(
            fetchResource: fn (): SiteData => $this->get($serverId, $siteId),
            isReady: fn (SiteData $site): bool => $site->isInstalled(),
            maxAttempts: $maxAttempts,
            sleepSeconds: $sleepSeconds,
            resourceLabel: "site installation (site {$siteId})",
            hasFailed: fn (SiteData $site): bool => $site->hasFailedInstallation(),
            describe: fn (SiteData $site): string => "status={$site->status}, repository_status=" . ($site->repositoryStatus ?? 'null'),
        );
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

    public function updateDeploymentScript(int $serverId, int $siteId, string $content): void
    {
        $this->connector->send(new UpdateDeploymentScriptRequest($serverId, $siteId, $content));
    }

    public function enableQuickDeploy(int $serverId, int $siteId): void
    {
        $this->connector->send(new EnableQuickDeployRequest($serverId, $siteId));
    }

    public function getEnvironmentFile(int $serverId, int $siteId): string
    {
        $request = new GetEnvironmentFileRequest($serverId, $siteId);
        $response = $this->connector->send($request);

        return $request->createDtoFromResponse($response);
    }

    public function updateEnvironmentFile(int $serverId, int $siteId, string $content): void
    {
        $this->connector->send(new UpdateEnvironmentFileRequest($serverId, $siteId, $content));
    }

    /**
     * The environment file update endpoint is asynchronous: it returns 202
     * before Forge has actually written the file. Poll the file back until
     * it contains the expected marker before relying on it (for example,
     * before triggering a deploy that reads it).
     */
    public function waitForEnvironmentFileContaining(
        int $serverId,
        int $siteId,
        string $expectedSubstring,
        int $maxAttempts = self::DEFAULT_ENVIRONMENT_MAX_ATTEMPTS,
        int $sleepSeconds = self::DEFAULT_ENVIRONMENT_SLEEP_SECONDS,
    ): string {
        return $this->waitUntil(
            fetchResource: fn (): string => $this->getEnvironmentFile($serverId, $siteId),
            isReady: fn (string $content): bool => str_contains($content, $expectedSubstring),
            maxAttempts: $maxAttempts,
            sleepSeconds: $sleepSeconds,
            resourceLabel: "environment file update (site {$siteId})",
            describe: fn (string $content): string => 'content length=' . mb_strlen($content) . " bytes, missing=\"{$expectedSubstring}\"",
        );
    }
}
