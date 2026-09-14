<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Integrations\Forge;

use Ddr\ForgeTestBranches\Exceptions\ConfigurationException;
use Ddr\ForgeTestBranches\Integrations\Forge\Resources\{DatabaseResource, DatabaseUserResource, DomainResource, SiteResource};

class ForgeClient
{
    protected ForgeConnector $connector;

    public function __construct(?string $token = null, ?string $organization = null, ?ForgeConnector $connector = null)
    {
        if ($connector instanceof ForgeConnector) {
            $this->connector = $connector;

            return;
        }

        $this->connector = new ForgeConnector(
            $this->resolveToken($token),
            $this->resolveOrganization($organization),
        );
    }

    public function sites(): SiteResource
    {
        return new SiteResource($this->connector);
    }

    public function domains(): DomainResource
    {
        return new DomainResource($this->connector);
    }

    public function databases(): DatabaseResource
    {
        return new DatabaseResource($this->connector);
    }

    public function databaseUsers(): DatabaseUserResource
    {
        return new DatabaseUserResource($this->connector);
    }

    private function resolveToken(?string $token): string
    {
        $resolved = $token ?? (string) config('forge-test-branches.forge_api_token');

        if ($resolved === '') {
            throw ConfigurationException::missingToken();
        }

        return $resolved;
    }

    private function resolveOrganization(?string $organization): string
    {
        $resolved = $organization ?? (string) config('forge-test-branches.organization');

        if ($resolved === '') {
            throw ConfigurationException::missingOrganization();
        }

        return $resolved;
    }
}
