<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\CertificateData;
use Ddr\ForgeTestBranches\Integrations\Forge\ForgeConnector;
use Ddr\ForgeTestBranches\Integrations\Forge\Requests\Domains\GetCertificateRequest;
use Saloon\Http\Faking\{MockClient, MockResponse};

test('resolve endpoint corretamente', function (): void {
    $request = new GetCertificateRequest(123, 456, 10, 5);

    expect($request->resolveEndpoint())->toBe('/servers/123/sites/456/domains/10/certificates/5');
});

test('usa método GET', function (): void {
    $request = new GetCertificateRequest(123, 456, 10, 5);

    expect($request->getMethod()->value)->toBe('GET');
});

test('retorna CertificateData do response', function (): void {
    $mockClient = new MockClient([
        GetCertificateRequest::class => MockResponse::make(forgeDocument(forgeResource('certificates', 5, forgeCertificateAttributes()))),
    ]);

    $connector = new ForgeConnector('test-token', 'test-org');
    $connector->withMockClient($mockClient);

    $request = new GetCertificateRequest(123, 456, 10, 5);
    $response = $connector->send($request);

    expect($request->createDtoFromResponse($response))->toBeInstanceOf(CertificateData::class)
        ->id->toBe(5)
        ->serverId->toBe(123)
        ->siteId->toBe(456)
        ->domainId->toBe(10)
        ->status->toBe('installed')
        ->active->toBeTrue();
});
