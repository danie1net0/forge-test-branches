<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Exceptions\ConfigurationException;
use Ddr\ForgeTestBranches\Integrations\Forge\{ForgeClient, ForgeConnector};
use Ddr\ForgeTestBranches\Integrations\Forge\Requests\Sites\ListSitesRequest;
use Ddr\ForgeTestBranches\Integrations\Forge\Resources\{DatabaseResource, DatabaseUserResource, DomainResource, SiteResource};
use Saloon\Http\Faking\{MockClient, MockResponse};

test('throws exception when token is not configured', function (): void {
    config([
        'forge-test-branches.forge_api_token' => null,
        'forge-test-branches.organization' => 'test-org',
    ]);

    new ForgeClient();
})->throws(ConfigurationException::class, 'Forge API token not configured');

test('throws exception when organization is not configured', function (): void {
    config([
        'forge-test-branches.forge_api_token' => 'test-token',
        'forge-test-branches.organization' => null,
    ]);

    new ForgeClient();
})->throws(ConfigurationException::class, 'Forge organization not configured');

test('creates client using token and organization from config', function (): void {
    config([
        'forge-test-branches.forge_api_token' => 'config-token',
        'forge-test-branches.organization' => 'config-org',
    ]);

    $client = new ForgeClient();

    $reflection = new ReflectionClass($client);
    $connector = $reflection->getProperty('connector')->getValue($client);

    expect($connector->resolveBaseUrl())->toBe('https://forge.laravel.com/api/orgs/config-org');
});

test('creates client using token and organization passed in the constructor, ignoring config', function (): void {
    config([
        'forge-test-branches.forge_api_token' => null,
        'forge-test-branches.organization' => null,
    ]);

    $client = new ForgeClient('custom-token', 'custom-org');

    $reflection = new ReflectionClass($client);
    $connector = $reflection->getProperty('connector')->getValue($client);

    expect($connector->resolveBaseUrl())->toBe('https://forge.laravel.com/api/orgs/custom-org');
});

test('accepts an injected connector, bypassing token and organization resolution entirely', function (): void {
    config([
        'forge-test-branches.forge_api_token' => null,
        'forge-test-branches.organization' => null,
    ]);

    $connector = new ForgeConnector('injected-token', 'injected-org');
    $mockClient = new MockClient([ListSitesRequest::class => MockResponse::make(forgeCollection([]))]);
    $connector->withMockClient($mockClient);

    $client = new ForgeClient(connector: $connector);
    $client->sites()->list(123);

    $mockClient->assertSentCount(1);
    expect($mockClient->getLastPendingRequest()->headers()->get('Authorization'))->toBe('Bearer injected-token');
});

test('isConfigured reflete a presença de token e organização no config', function (?string $token, ?string $organization, bool $expected): void {
    config([
        'forge-test-branches.forge_api_token' => $token,
        'forge-test-branches.organization' => $organization,
    ]);

    expect(ForgeClient::isConfigured())->toBe($expected);
})->with([
    'ambos presentes' => ['token', 'org', true],
    'token ausente' => [null, 'org', false],
    'organização ausente' => ['token', null, false],
    'token vazio' => ['', 'org', false],
    'ambos ausentes' => [null, null, false],
]);

test('returns SiteResource instance', function (): void {
    $client = new ForgeClient('test-token', 'test-org');

    expect($client->sites())->toBeInstanceOf(SiteResource::class);
});

test('returns DomainResource instance', function (): void {
    $client = new ForgeClient('test-token', 'test-org');

    expect($client->domains())->toBeInstanceOf(DomainResource::class);
});

test('returns DatabaseResource instance', function (): void {
    $client = new ForgeClient('test-token', 'test-org');

    expect($client->databases())->toBeInstanceOf(DatabaseResource::class);
});

test('returns DatabaseUserResource instance', function (): void {
    $client = new ForgeClient('test-token', 'test-org');

    expect($client->databaseUsers())->toBeInstanceOf(DatabaseUserResource::class);
});
