<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Integrations\Forge;

use Ddr\ForgeTestBranches\Integrations\Forge\Resources\{DatabaseResource, DatabaseUserResource, SiteResource};
use RuntimeException;

class ForgeClient
{
    protected ForgeConnector $connector;

    public function __construct(?string $token = null)
    {
        $token ??= config('forge-test-branches.forge_api_token');

        if (! $token) {
            throw new RuntimeException('Forge API token not configured');
        }

        $this->connector = new ForgeConnector($token);
    }

    public function sites(): SiteResource
    {
        return new SiteResource($this->connector);
    }

    public function databases(): DatabaseResource
    {
        return new DatabaseResource($this->connector);
    }

    public function databaseUsers(): DatabaseUserResource
    {
        return new DatabaseUserResource($this->connector);
    }
}
