<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\DomainData;
use Ddr\ForgeTestBranches\Integrations\Forge\ForgeConnector;
use Ddr\ForgeTestBranches\Integrations\Forge\Requests\Domains\ListDomainsRequest;
use Saloon\Http\Faking\{MockClient, MockResponse};

test('resolve endpoint corretamente', function (): void {
    $request = new ListDomainsRequest(123, 456);

    expect($request->resolveEndpoint())->toBe('/servers/123/sites/456/domains');
});

test('usa método GET', function (): void {
    $request = new ListDomainsRequest(123, 456);

    expect($request->getMethod()->value)->toBe('GET');
});

test('não expõe filtro por nome, que a API não aceita para domínios', function (): void {
    $request = new ListDomainsRequest(123, 456);

    expect(method_exists($request, 'filterByName'))->toBeFalse();
});

test('lista domínios e retorna array de DTOs', function (): void {
    $mockClient = new MockClient([
        ListDomainsRequest::class => MockResponse::make(forgeCollection([
            forgeResource('domains', 10, forgeDomainAttributes('feat-login.review.example.com')),
        ])),
    ]);

    $connector = new ForgeConnector('test-token', 'test-org');
    $connector->withMockClient($mockClient);

    $request = new ListDomainsRequest(123, 456);
    $response = $connector->send($request);
    $result = $request->createDtoFromResponse($response);

    expect($result)->toHaveCount(1)
        ->and($result[0])->toBeInstanceOf(DomainData::class)
        ->id->toBe(10)
        ->serverId->toBe(123)
        ->siteId->toBe(456)
        ->name->toBe('feat-login.review.example.com');
});
