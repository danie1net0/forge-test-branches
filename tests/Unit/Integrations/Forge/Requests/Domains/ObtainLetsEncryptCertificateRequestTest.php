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

test('envia http-01, chave ecdsa e deixa o Forge ativar o certificado após a emissão', function (): void {
    $request = new ObtainLetsEncryptCertificateRequest(123, 456, 10);

    expect($request->body()->all())->toBe([
        'type' => 'letsencrypt',
        'enable' => false,
        'letsencrypt' => ['verification_method' => 'http-01', 'key_type' => 'ecdsa'],
    ]);
});

test('aceita um método de verificação diferente', function (): void {
    $request = new ObtainLetsEncryptCertificateRequest(123, 456, 10, 'dns-01');

    expect($request->body()->all())->toHaveKey('letsencrypt', ['verification_method' => 'dns-01', 'key_type' => 'ecdsa']);
});

test('aceita um tipo de chave diferente', function (): void {
    $request = new ObtainLetsEncryptCertificateRequest(123, 456, 10, 'http-01', 'rsa');

    expect($request->body()->all())->toHaveKey('letsencrypt', ['verification_method' => 'http-01', 'key_type' => 'rsa']);
});

test('aceita active nulo enquanto o certificado é emitido', function (): void {
    $mockClient = new MockClient([
        ObtainLetsEncryptCertificateRequest::class => MockResponse::make(forgeDocument(forgeResource('certificates', 5, forgeCertificateAttributes([
            'status' => 'installing',
            'request_status' => 'creating',
            'active' => null,
        ]))), 202),
    ]);

    $connector = new ForgeConnector('test-token', 'test-org');
    $connector->withMockClient($mockClient);

    $request = new ObtainLetsEncryptCertificateRequest(123, 456, 10);
    $certificate = $request->createDtoFromResponse($connector->send($request));

    expect($certificate->active)->toBeNull()
        ->and($certificate->isReady())->toBeFalse();
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
