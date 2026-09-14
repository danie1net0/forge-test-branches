<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\CertificateData;

test('creates instance with all parameters', function (): void {
    $data = new CertificateData(
        id: 123,
        serverId: 456,
        siteId: 789,
        domainId: 10,
        type: 'letsencrypt',
        requestStatus: 'creating',
        status: 'installing',
        active: false,
        createdAt: '2025-07-29T09:00:00Z',
    );

    expect($data)
        ->id->toBe(123)
        ->serverId->toBe(456)
        ->siteId->toBe(789)
        ->domainId->toBe(10)
        ->type->toBe('letsencrypt')
        ->requestStatus->toBe('creating')
        ->status->toBe('installing')
        ->active->toBeFalse()
        ->createdAt->toBe('2025-07-29T09:00:00Z');
});

test('considera pronto apenas certificado instalado e ativo', function (string $status, bool $active, bool $expected): void {
    $data = new CertificateData(
        id: 123,
        serverId: 456,
        siteId: 789,
        domainId: 10,
        type: 'letsencrypt',
        requestStatus: 'created',
        status: $status,
        active: $active,
    );

    expect($data->isReady())->toBe($expected);
})->with([
    'instalado e ativo' => ['installed', true, true],
    'instalado mas inativo' => ['installed', false, false],
    'ainda instalando' => ['installing', true, false],
    'falhou' => ['failed', false, false],
]);

test('detecta status de falha do certificado', function (string $status, bool $expected): void {
    $data = new CertificateData(
        id: 123,
        serverId: 456,
        siteId: 789,
        domainId: 10,
        type: 'letsencrypt',
        requestStatus: 'created',
        status: $status,
        active: false,
    );

    expect($data->hasFailed())->toBe($expected);
})->with([
    'falhou' => ['failed', true],
    'falha desconhecida' => ['failed-unknown', true],
    'falha do runner' => ['failed-runner', true],
    'instalando' => ['installing', false],
    'instalado' => ['installed', false],
]);

test('não considera falha um status desconhecido', function (): void {
    $data = new CertificateData(
        id: 123,
        serverId: 456,
        siteId: 789,
        domainId: 10,
        type: 'letsencrypt',
        requestStatus: 'created',
        status: 'some-future-status',
        active: false,
    );

    expect($data->hasFailed())->toBeFalse();
});
