<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\SiteData;
use Ddr\ForgeTestBranches\Integrations\Forge\ForgeConnector;
use Ddr\ForgeTestBranches\Integrations\Forge\Requests\Sites\GetSiteRequest;
use Saloon\Http\Faking\{MockClient, MockResponse};

test('resolve endpoint corretamente', function (): void {
    $request = new GetSiteRequest(123, 456);

    expect($request->resolveEndpoint())->toBe('/sites/456');
});

test('usa método GET', function (): void {
    $request = new GetSiteRequest(123, 456);

    expect($request->getMethod()->value)->toBe('GET');
});

test('retorna SiteData do response', function (): void {
    $mockClient = new MockClient([
        GetSiteRequest::class => MockResponse::make(forgeDocument(forgeResource('sites', 456, forgeSiteAttributes('test.example.com')))),
    ]);

    $connector = new ForgeConnector('test-token', 'test-org');
    $connector->withMockClient($mockClient);

    $request = new GetSiteRequest(123, 456);
    $response = $connector->send($request);
    $result = $request->createDtoFromResponse($response);

    expect($result)->toBeInstanceOf(SiteData::class)
        ->id->toBe(456)
        ->serverId->toBe(123)
        ->name->toBe('test.example.com')
        ->status->toBe('installed')
        ->repositoryProvider->toBe('gitlab')
        ->repositoryUrl->toBe('user/repo')
        ->repositoryBranch->toBe('main')
        ->repositoryStatus->toBe('installed')
        ->quickDeploy->toBeTrue()
        ->phpVersion->toBe('php84');
});

test('aceita site sem repositório', function (): void {
    $mockClient = new MockClient([
        GetSiteRequest::class => MockResponse::make(forgeDocument(forgeResource('sites', 456, forgeSiteAttributes('test.example.com', [
            'repository' => null,
        ])))),
    ]);

    $connector = new ForgeConnector('test-token', 'test-org');
    $connector->withMockClient($mockClient);

    $request = new GetSiteRequest(123, 456);
    $response = $connector->send($request);

    expect($request->createDtoFromResponse($response))
        ->repositoryProvider->toBeNull()
        ->repositoryBranch->toBeNull()
        ->repositoryStatus->toBeNull();
});
