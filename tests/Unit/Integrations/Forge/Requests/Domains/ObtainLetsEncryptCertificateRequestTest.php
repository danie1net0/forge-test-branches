<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\CertificateData;
use Ddr\ForgeTestBranches\Integrations\Forge\ForgeConnector;
use Ddr\ForgeTestBranches\Integrations\Forge\Requests\Domains\ObtainLetsEncryptCertificateRequest;
use Saloon\Http\Faking\{MockClient, MockResponse};

test('resolve endpoint corretamente', function (): void {
    $request = new ObtainLetsEncryptCertificateRequest(123, 456, 10);

    expect($request->resolveEndpoint())->toBe('/servers/123/sites/456/domains/10/certificates');
});

test('usa método POST', function (): void {
    $request = new ObtainLetsEncryptCertificateRequest(123, 456, 10);

    expect($request->getMethod()->value)->toBe('POST');
});

test('envia o método de verificação http-01 por padrão', function (): void {
    $request = new ObtainLetsEncryptCertificateRequest(123, 456, 10);

    expect($request->body()->all())->toBe([
        'type' => 'letsencrypt',
        'enable' => true,
        'letsencrypt' => ['verification_method' => 'http-01'],
    ]);
});

test('aceita um método de verificação diferente', function (): void {
    $request = new ObtainLetsEncryptCertificateRequest(123, 456, 10, 'dns-01');

    expect($request->body()->all())->toHaveKey('letsencrypt', ['verification_method' => 'dns-01']);
});

test('retorna CertificateData do response', function (): void {
    $mockClient = new MockClient([
        ObtainLetsEncryptCertificateRequest::class => MockResponse::make(forgeDocument(forgeResource('certificates', 5, forgeCertificateAttributes([
            'status' => 'installing',
            'request_status' => 'creating',
            'active' => false,
        ]))), 202),
    ]);

    $connector = new ForgeConnector('test-token', 'test-org');
    $connector->withMockClient($mockClient);

    $request = new ObtainLetsEncryptCertificateRequest(123, 456, 10);
    $response = $connector->send($request);

    expect($request->createDtoFromResponse($response))->toBeInstanceOf(CertificateData::class)
        ->id->toBe(5)
        ->serverId->toBe(123)
        ->siteId->toBe(456)
        ->domainId->toBe(10)
        ->status->toBe('installing');
});
