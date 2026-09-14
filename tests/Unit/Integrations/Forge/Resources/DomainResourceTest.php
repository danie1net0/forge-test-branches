<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\{CertificateData, DomainData};
use Ddr\ForgeTestBranches\Exceptions\{DomainNotFoundException, ResourceFailedException, ResourceTimeoutException};
use Ddr\ForgeTestBranches\Integrations\Forge\ForgeConnector;
use Ddr\ForgeTestBranches\Integrations\Forge\Requests\Domains\{GetCertificateRequest, ListDomainsRequest, ObtainLetsEncryptCertificateRequest};
use Ddr\ForgeTestBranches\Integrations\Forge\Resources\DomainResource;
use Illuminate\Support\Sleep;
use Saloon\Http\Faking\{MockClient, MockResponse};

function makeDomainResource(MockClient $mockClient): DomainResource
{
    $connector = new ForgeConnector('test-token', 'test-org');
    $connector->withMockClient($mockClient);

    return new DomainResource($connector);
}

test('lista domínios do site', function (): void {
    $mockClient = new MockClient([
        ListDomainsRequest::class => MockResponse::make(forgeCollection([
            forgeResource('domainRecords', 10, forgeDomainAttributes('feat.review.example.com')),
            forgeResource('domainRecords', 11, forgeDomainAttributes('feat.on-forge.com', ['type' => 'alias'])),
        ])),
    ]);

    $result = makeDomainResource($mockClient)->list(123, 456);

    expect($result)->toHaveCount(2)
        ->and($result[0])->toBeInstanceOf(DomainData::class)
        ->id->toBe(10)
        ->serverId->toBe(123)
        ->siteId->toBe(456)
        ->name->toBe('feat.review.example.com')
        ->type->toBe('primary')
        ->and($mockClient->getLastPendingRequest()->getUrl())->toBe('https://forge.laravel.com/api/orgs/test-org/servers/123/sites/456/domains');
});

test('encontra domínio pelo nome', function (): void {
    $mockClient = new MockClient([
        ListDomainsRequest::class => MockResponse::make(forgeCollection([
            forgeResource('domainRecords', 11, forgeDomainAttributes('feat.on-forge.com', ['type' => 'alias'])),
            forgeResource('domainRecords', 10, forgeDomainAttributes('feat.review.example.com')),
        ])),
    ]);

    expect(makeDomainResource($mockClient)->findByName(123, 456, 'feat.review.example.com'))
        ->toBeInstanceOf(DomainData::class)
        ->id->toBe(10);
});

test('retorna null quando domínio não é encontrado', function (): void {
    $mockClient = new MockClient([
        ListDomainsRequest::class => MockResponse::make(forgeCollection([])),
    ]);

    expect(makeDomainResource($mockClient)->findByName(123, 456, 'feat.review.example.com'))->toBeNull();
});

test('aguarda o domínio ficar habilitado', function (): void {
    Sleep::fake();

    $mockClient = new MockClient([
        MockResponse::make(forgeCollection([forgeResource('domainRecords', 10, forgeDomainAttributes('feat.review.example.com', ['status' => 'connecting']))])),
        MockResponse::make(forgeCollection([forgeResource('domainRecords', 10, forgeDomainAttributes('feat.review.example.com', ['status' => 'enabled']))])),
    ]);

    $domain = makeDomainResource($mockClient)->waitForEnabled(123, 456, 10, 'feat.review.example.com', 5, 1);

    expect($domain)->toBeInstanceOf(DomainData::class)
        ->status->toBe('enabled');

    Sleep::assertSequence([Sleep::for(1)->second()]);
});

test('lança exceção quando o domínio nunca fica habilitado', function (): void {
    Sleep::fake();

    $mockClient = new MockClient([
        MockResponse::make(forgeCollection([forgeResource('domainRecords', 10, forgeDomainAttributes('feat.review.example.com', ['status' => 'connecting']))])),
        MockResponse::make(forgeCollection([forgeResource('domainRecords', 10, forgeDomainAttributes('feat.review.example.com', ['status' => 'connecting']))])),
    ]);

    $action = fn (): DomainData => makeDomainResource($mockClient)->waitForEnabled(123, 456, 10, 'feat.review.example.com', 2, 1);

    expect($action)->toThrow(ResourceTimeoutException::class, 'Timeout waiting for domain activation (domain 10) after 2 attempts');
});

test('lança exceção quando o domínio some durante a espera', function (): void {
    Sleep::fake();

    $mockClient = new MockClient([
        ListDomainsRequest::class => MockResponse::make(forgeCollection([])),
    ]);

    $action = fn (): DomainData => makeDomainResource($mockClient)->waitForEnabled(123, 456, 10, 'feat.review.example.com', 2, 1);

    expect($action)->toThrow(DomainNotFoundException::class, 'Domain not found on site: feat.review.example.com');
});

test('solicita certificado lets encrypt para o domínio com verificação http-01 por padrão', function (): void {
    $mockClient = new MockClient([
        ObtainLetsEncryptCertificateRequest::class => MockResponse::make(forgeDocument(forgeResource('certificates', 5, forgeCertificateAttributes([
            'status' => 'installing',
            'request_status' => 'creating',
            'active' => false,
        ]))), 202),
    ]);

    $certificate = makeDomainResource($mockClient)->obtainLetsEncryptCertificate(123, 456, 10);

    expect($certificate)->toBeInstanceOf(CertificateData::class)
        ->id->toBe(5)
        ->serverId->toBe(123)
        ->siteId->toBe(456)
        ->domainId->toBe(10)
        ->type->toBe('letsencrypt')
        ->status->toBe('installing')
        ->active->toBeFalse()
        ->and($mockClient->getLastPendingRequest())
        ->getUrl()->toBe('https://forge.laravel.com/api/orgs/test-org/servers/123/sites/456/domains/10/certificates')
        ->body()->all()->toBe(['type' => 'letsencrypt', 'enable' => true, 'letsencrypt' => ['verification_method' => 'http-01']]);
});

test('obtém detalhes do certificado', function (): void {
    $mockClient = new MockClient([
        GetCertificateRequest::class => MockResponse::make(forgeDocument(forgeResource('certificates', 5, forgeCertificateAttributes()))),
    ]);

    $certificate = makeDomainResource($mockClient)->getCertificate(123, 456, 10, 5);

    expect($certificate)->toBeInstanceOf(CertificateData::class)
        ->id->toBe(5)
        ->status->toBe('installed')
        ->active->toBeTrue()
        ->and($mockClient->getLastPendingRequest()->getUrl())->toBe('https://forge.laravel.com/api/orgs/test-org/servers/123/sites/456/domains/10/certificates/5');
});

test('aguarda ativação do certificado', function (): void {
    Sleep::fake();

    $mockClient = new MockClient([
        MockResponse::make(forgeDocument(forgeResource('certificates', 5, forgeCertificateAttributes(['status' => 'installing', 'active' => false])))),
        MockResponse::make(forgeDocument(forgeResource('certificates', 5, forgeCertificateAttributes()))),
    ]);

    $certificate = makeDomainResource($mockClient)->waitForCertificateActivation(123, 456, 10, 5, 5, 1);

    expect($certificate)
        ->active->toBeTrue()
        ->status->toBe('installed');
});

test('lança exceção quando ativação do certificado expira depois de várias tentativas', function (): void {
    Sleep::fake();

    $mockClient = new MockClient([
        MockResponse::make(forgeDocument(forgeResource('certificates', 5, forgeCertificateAttributes(['status' => 'installing', 'active' => false])))),
        MockResponse::make(forgeDocument(forgeResource('certificates', 5, forgeCertificateAttributes(['status' => 'installing', 'active' => false])))),
        MockResponse::make(forgeDocument(forgeResource('certificates', 5, forgeCertificateAttributes(['status' => 'installing', 'active' => false])))),
    ]);

    $action = fn (): CertificateData => makeDomainResource($mockClient)->waitForCertificateActivation(123, 456, 10, 5, 3, 1);

    expect($action)->toThrow(ResourceTimeoutException::class, 'Timeout waiting for SSL certificate activation (certificate 5) after 3 attempts');
    $mockClient->assertSentCount(3);
});

test('lança exceção imediatamente quando o certificado falha', function (string $status): void {
    Sleep::fake();

    $mockClient = new MockClient([
        MockResponse::make(forgeDocument(forgeResource('certificates', 5, forgeCertificateAttributes(['status' => $status, 'active' => false])))),
    ]);

    $action = fn (): CertificateData => makeDomainResource($mockClient)->waitForCertificateActivation(123, 456, 10, 5, 60, 5);

    expect($action)->toThrow(ResourceFailedException::class);
    $mockClient->assertSentCount(1);
    Sleep::assertNeverSlept();
})->with([
    'falhou' => ['failed'],
    'falha desconhecida' => ['failed-unknown'],
    'falha do runner' => ['failed-runner'],
]);
