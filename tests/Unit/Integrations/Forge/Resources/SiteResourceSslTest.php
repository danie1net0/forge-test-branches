<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\CertificateData;
use Ddr\ForgeTestBranches\Integrations\Forge\{ForgeConnector, Resources\SiteResource};
use Saloon\Http\{Faking\MockClient, Faking\MockResponse};

test('obtains lets encrypt certificate successfully', function (): void {
    $mockClient = new MockClient([
        MockResponse::make([
            'certificate' => [
                'id' => 123,
                'domains' => ['example.com'],
                'request_status' => 'created',
                'status' => 'installing',
                'existing' => false,
                'active' => false,
                'created_at' => '2024-01-01 00:00:00',
                'activated_at' => null,
            ],
        ]),
    ]);

    $connector = new ForgeConnector('fake-token');
    $connector->withMockClient($mockClient);

    $resource = new SiteResource($connector);
    $certificate = $resource->obtainLetsEncryptCertificate(123, 456, ['example.com']);

    expect($certificate)->toBeInstanceOf(CertificateData::class)
        ->id->toBe(123)
        ->serverId->toBe(123)
        ->siteId->toBe(456)
        ->domains->toBe(['example.com'])
        ->status->toBe('installing')
        ->active->toBeFalse();
});

test('gets certificate details', function (): void {
    $mockClient = new MockClient([
        MockResponse::make([
            'certificate' => [
                'id' => 123,
                'domains' => ['example.com'],
                'request_status' => 'created',
                'status' => 'installed',
                'existing' => false,
                'active' => true,
                'created_at' => '2024-01-01 00:00:00',
                'activated_at' => '2024-01-01 00:05:00',
            ],
        ]),
    ]);

    $connector = new ForgeConnector('fake-token');
    $connector->withMockClient($mockClient);

    $resource = new SiteResource($connector);
    $certificate = $resource->getCertificate(123, 456, 789);

    expect($certificate)->toBeInstanceOf(CertificateData::class)
        ->id->toBe(123)
        ->status->toBe('installed')
        ->active->toBeTrue();
});

test('waits for certificate activation', function (): void {
    $mockClient = new MockClient([
        MockResponse::make([
            'certificate' => [
                'id' => 123,
                'domains' => ['example.com'],
                'request_status' => 'created',
                'status' => 'installing',
                'existing' => false,
                'active' => false,
                'created_at' => '2024-01-01 00:00:00',
                'activated_at' => null,
            ],
        ]),
        MockResponse::make([
            'certificate' => [
                'id' => 123,
                'domains' => ['example.com'],
                'request_status' => 'created',
                'status' => 'installed',
                'existing' => false,
                'active' => true,
                'created_at' => '2024-01-01 00:00:00',
                'activated_at' => '2024-01-01 00:05:00',
            ],
        ]),
    ]);

    $connector = new ForgeConnector('fake-token');
    $connector->withMockClient($mockClient);

    $resource = new SiteResource($connector);
    $certificate = $resource->waitForCertificateActivation(123, 456, 789, 2, 0);

    expect($certificate->active)->toBeTrue()
        ->and($certificate->status)->toBe('installed');
});

test('throws exception when certificate activation times out', function (): void {
    $mockClient = new MockClient([
        MockResponse::make([
            'certificate' => [
                'id' => 123,
                'domains' => ['example.com'],
                'request_status' => 'created',
                'status' => 'installing',
                'existing' => false,
                'active' => false,
                'created_at' => '2024-01-01 00:00:00',
                'activated_at' => null,
            ],
        ]),
    ]);

    $connector = new ForgeConnector('fake-token');
    $connector->withMockClient($mockClient);

    $resource = new SiteResource($connector);
    $resource->waitForCertificateActivation(123, 456, 789, 1, 0);
})->throws(RuntimeException::class, 'Timeout waiting for SSL certificate activation');
