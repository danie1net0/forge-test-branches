<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\SiteData;
use Ddr\ForgeTestBranches\Integrations\Forge\ForgeConnector;
use Ddr\ForgeTestBranches\Integrations\Forge\Requests\Sites\ListSitesRequest;
use Saloon\Http\Faking\{MockClient, MockResponse};

test('resolve endpoint corretamente', function (): void {
    $request = new ListSitesRequest(123);

    expect($request->resolveEndpoint())->toBe('/servers/123/sites');
});

test('usa método GET', function (): void {
    $request = new ListSitesRequest(123);

    expect($request->getMethod()->value)->toBe('GET');
});

test('filtra pelo nome', function (): void {
    $request = new ListSitesRequest(123)->filterByName('test.example.com');

    expect($request->query()->get('filter[name]'))->toBe('test.example.com');
});

test('lista sites e retorna array de DTOs', function (): void {
    $mockClient = new MockClient([
        ListSitesRequest::class => MockResponse::make(forgeCollection([
            forgeResource('sites', 1, forgeSiteAttributes('test.example.com')),
            forgeResource('sites', 2, forgeSiteAttributes('site2.example.com')),
        ])),
    ]);

    $connector = new ForgeConnector('test-token', 'test-org');
    $connector->withMockClient($mockClient);

    $request = new ListSitesRequest(123);
    $response = $connector->send($request);
    $result = $request->createDtoFromResponse($response);

    expect($result)->toHaveCount(2)
        ->and($result[0])->toBeInstanceOf(SiteData::class)
        ->id->toBe(1)
        ->serverId->toBe(123)
        ->name->toBe('test.example.com')
        ->and($result[1])->toBeInstanceOf(SiteData::class)
        ->id->toBe(2)
        ->name->toBe('site2.example.com');
});

test('retorna array vazio quando não há sites', function (): void {
    $mockClient = new MockClient([
        ListSitesRequest::class => MockResponse::make(forgeCollection([])),
    ]);

    $connector = new ForgeConnector('test-token', 'test-org');
    $connector->withMockClient($mockClient);

    $request = new ListSitesRequest(123);
    $response = $connector->send($request);

    expect($request->createDtoFromResponse($response))->toBeEmpty();
});
