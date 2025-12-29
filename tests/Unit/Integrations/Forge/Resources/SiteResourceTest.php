<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Integrations\Forge\ForgeConnector;
use Ddr\ForgeTestBranches\Integrations\Forge\Resources\SiteResource;
use Saloon\Http\Faking\{MockClient, MockResponse};
use Ddr\ForgeTestBranches\Integrations\Forge\Requests\Sites\{DeleteSiteRequest, DeploySiteRequest, EnableQuickDeployRequest, GetEnvironmentRequest, UpdateDeploymentScriptRequest, UpdateEnvironmentRequest};

test('deletes site successfully', function (): void {
    $mockClient = new MockClient([
        DeleteSiteRequest::class => MockResponse::make([]),
    ]);

    $connector = new ForgeConnector('test-token');
    $connector->withMockClient($mockClient);

    $resource = new SiteResource($connector);
    $resource->delete(123, 456);

    $mockClient->assertSent(DeleteSiteRequest::class);
});

test('deploys site successfully', function (): void {
    $mockClient = new MockClient([
        DeploySiteRequest::class => MockResponse::make([]),
    ]);

    $connector = new ForgeConnector('test-token');
    $connector->withMockClient($mockClient);

    $resource = new SiteResource($connector);
    $resource->deploy(123, 456);

    $mockClient->assertSent(DeploySiteRequest::class);
});

test('updates deployment script', function (): void {
    $mockClient = new MockClient([
        UpdateDeploymentScriptRequest::class => MockResponse::make([]),
    ]);

    $connector = new ForgeConnector('test-token');
    $connector->withMockClient($mockClient);

    $resource = new SiteResource($connector);
    $resource->updateDeploymentScript(123, 456, 'cd /home/forge && git pull');

    $mockClient->assertSent(UpdateDeploymentScriptRequest::class);
});

test('enables quick deploy', function (): void {
    $mockClient = new MockClient([
        EnableQuickDeployRequest::class => MockResponse::make([]),
    ]);

    $connector = new ForgeConnector('test-token');
    $connector->withMockClient($mockClient);

    $resource = new SiteResource($connector);
    $resource->enableQuickDeploy(123, 456);

    $mockClient->assertSent(EnableQuickDeployRequest::class);
});

test('gets site environment', function (): void {
    $mockClient = new MockClient([
        GetEnvironmentRequest::class => MockResponse::make('APP_ENV=production'),
    ]);

    $connector = new ForgeConnector('test-token');
    $connector->withMockClient($mockClient);

    $resource = new SiteResource($connector);
    $result = $resource->getEnvironment(123, 456);

    expect($result)->toBe('APP_ENV=production');
    $mockClient->assertSent(GetEnvironmentRequest::class);
});

test('updates site environment', function (): void {
    $mockClient = new MockClient([
        UpdateEnvironmentRequest::class => MockResponse::make([]),
    ]);

    $connector = new ForgeConnector('test-token');
    $connector->withMockClient($mockClient);

    $resource = new SiteResource($connector);
    $resource->updateEnvironment(123, 456, 'APP_ENV=staging');

    $mockClient->assertSent(UpdateEnvironmentRequest::class);
});
