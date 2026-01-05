<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\CertificateData;

test('creates instance with all parameters', function (): void {
    $data = new CertificateData(
        id: 123,
        serverId: 456,
        siteId: 789,
        domains: ['example.com', 'www.example.com'],
        requestStatus: 'created',
        status: 'installing',
        existing: false,
        active: false,
        createdAt: '2024-01-01 00:00:00',
        activatedAt: null,
    );

    expect($data)
        ->id->toBe(123)
        ->serverId->toBe(456)
        ->siteId->toBe(789)
        ->domains->toBe(['example.com', 'www.example.com'])
        ->requestStatus->toBe('created')
        ->status->toBe('installing')
        ->existing->toBeFalse()
        ->active->toBeFalse()
        ->createdAt->toBe('2024-01-01 00:00:00')
        ->activatedAt->toBeNull();
});

test('creates instance with activated certificate', function (): void {
    $data = new CertificateData(
        id: 123,
        serverId: 456,
        siteId: 789,
        domains: ['example.com'],
        requestStatus: 'created',
        status: 'installed',
        existing: false,
        active: true,
        createdAt: '2024-01-01 00:00:00',
        activatedAt: '2024-01-01 00:05:00',
    );

    expect($data->active)->toBeTrue()
        ->and($data->status)->toBe('installed')
        ->and($data->activatedAt)->toBe('2024-01-01 00:05:00');
});
