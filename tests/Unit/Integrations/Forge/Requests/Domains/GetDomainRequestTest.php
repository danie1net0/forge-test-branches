<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\DomainData;
use Ddr\ForgeTestBranches\Integrations\Forge\ForgeConnector;
use Ddr\ForgeTestBranches\Integrations\Forge\Requests\Domains\GetDomainRequest;
use Saloon\Http\Faking\{MockClient, MockResponse};

test('resolve endpoint corretamente', function (): void {
    $request = new GetDomainRequest(123, 456, 10);

    expect($request->resolveEndpoint())->toBe('/servers/123/sites/456/domains/10');
});

test('usa método GET', function (): void {
    $request = new GetDomainRequest(123, 456, 10);

    expect($request->getMethod()->value)->toBe('GET');
});

test('retorna DomainData do response', function (): void {
    $mockClient = new MockClient([
        GetDomainRequest::class => MockResponse::make(forgeDocument(forgeResource('domainRecords', 10, forgeDomainAttributes('feat-login.review.example.com')))),
    ]);

    $connector = new ForgeConnector('test-token', 'test-org');
    $connector->withMockClient($mockClient);

    $request = new GetDomainRequest(123, 456, 10);
    $response = $connector->send($request);

    expect($request->createDtoFromResponse($response))->toBeInstanceOf(DomainData::class)
        ->id->toBe(10)
        ->serverId->toBe(123)
        ->siteId->toBe(456)
        ->name->toBe('feat-login.review.example.com')
        ->status->toBe('enabled');
});
