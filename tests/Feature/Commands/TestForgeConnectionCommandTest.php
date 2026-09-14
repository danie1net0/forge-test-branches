<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\{DatabaseData, DatabaseUserData, SiteData};
use Ddr\ForgeTestBranches\Integrations\Forge\{ForgeClient, ForgeConnector};
use Ddr\ForgeTestBranches\Integrations\Forge\Requests\Sites\ListSitesRequest;
use Ddr\ForgeTestBranches\Integrations\Forge\Resources\{DatabaseResource, DatabaseUserResource, SiteResource};
use Saloon\Http\Faking\{MockClient, MockResponse};

beforeEach(function (): void {
    config([
        'forge-test-branches.server_id' => 12345,
        'forge-test-branches.forge_api_token' => 'test-token-1234567890',
        'forge-test-branches.organization' => 'test-org',
        'forge-test-branches.git.repository' => 'user/repo',
    ]);
});

function makeSiteDataForConnection(int $id, string $name): SiteData
{
    return new SiteData(
        id: $id,
        serverId: 12345,
        name: $name,
        status: 'installed',
    );
}

function bindForgeClientWithMockedResponse(int $status, mixed $body = ['message' => 'error']): MockClient
{
    $mockClient = new MockClient([
        ListSitesRequest::class => MockResponse::make($body, $status),
    ]);

    $connector = new ForgeConnector('test-token-1234567890', 'test-org');
    $connector->retryInterval = 1;
    $connector->withMockClient($mockClient);

    app()->instance(ForgeClient::class, new ForgeClient(connector: $connector));

    return $mockClient;
}

test('testa conexão com sucesso', function (): void {
    $siteResource = Mockery::mock(SiteResource::class);
    $databaseResource = Mockery::mock(DatabaseResource::class);
    $databaseUserResource = Mockery::mock(DatabaseUserResource::class);

    $siteResource->shouldReceive('list')
        ->once()
        ->with(12345)
        ->andReturn([
            makeSiteDataForConnection(1, 'site1.example.com'),
            makeSiteDataForConnection(2, 'site2.example.com'),
        ]);

    $databaseResource->shouldReceive('list')
        ->once()
        ->with(12345)
        ->andReturn([
            new DatabaseData(id: 1, serverId: 12345, name: 'db1', status: 'installed', createdAt: now()->toDateTimeString()),
        ]);

    $databaseUserResource->shouldReceive('list')
        ->once()
        ->with(12345)
        ->andReturn([
            new DatabaseUserData(id: 1, serverId: 12345, name: 'user1', status: 'installed', createdAt: now()->toDateTimeString()),
        ]);

    $forgeClient = Mockery::mock(ForgeClient::class);
    $forgeClient->shouldReceive('sites')->andReturn($siteResource);
    $forgeClient->shouldReceive('databases')->andReturn($databaseResource);
    $forgeClient->shouldReceive('databaseUsers')->andReturn($databaseUserResource);

    app()->instance(ForgeClient::class, $forgeClient);

    $this->artisan('forge-test-branches:test-connection')
        ->expectsOutputToContain('Testing Forge API connection...')
        ->expectsOutputToContain('Found 2 sites on server')
        ->expectsOutputToContain('Found 1 databases')
        ->expectsOutputToContain('Found 1 database users')
        ->expectsOutputToContain('All tests passed!')
        ->assertSuccessful();
});

test('exibe erro de configuração antes de tentar conectar quando a organização não está definida', function (): void {
    config(['forge-test-branches.organization' => null]);

    $this->artisan('forge-test-branches:test-connection')
        ->expectsOutputToContain('Configuration error!')
        ->expectsOutputToContain('Forge organization not configured')
        ->assertFailed();
});

test('exibe erro com dica para 404 quando conexão falha', function (): void {
    bindForgeClientWithMockedResponse(404);

    $this->artisan('forge-test-branches:test-connection')
        ->expectsOutputToContain('Connection failed!')
        ->expectsOutputToContain('Possible causes:')
        ->expectsOutputToContain('Invalid FORGE_ORGANIZATION or FORGE_SERVER_ID')
        ->assertFailed();
});

test('exibe erro com dica para 401 quando token é inválido', function (): void {
    bindForgeClientWithMockedResponse(401);

    $this->artisan('forge-test-branches:test-connection')
        ->expectsOutputToContain('Connection failed!')
        ->expectsOutputToContain('Possible causes:')
        ->expectsOutputToContain('Invalid FORGE_API_TOKEN')
        ->assertFailed();
});

test('exibe dica de escopos quando a API retorna 403', function (): void {
    bindForgeClientWithMockedResponse(403);

    $this->artisan('forge-test-branches:test-connection')
        ->expectsOutputToContain('Organization: test-org')
        ->expectsOutputToContain('Connection failed!')
        ->expectsOutputToContain('Invalid FORGE_API_TOKEN')
        ->expectsOutputToContain('Token is missing required scopes')
        ->assertFailed();
});

test('exibe dica de rate limit quando a API retorna 429', function (): void {
    bindForgeClientWithMockedResponse(429);

    $this->artisan('forge-test-branches:test-connection')
        ->expectsOutputToContain('Connection failed!')
        ->expectsOutputToContain('Rate limit exceeded')
        ->assertFailed();
});

test('exibe erro genérico quando conexão falha sem código HTTP', function (): void {
    $siteResource = Mockery::mock(SiteResource::class);
    $siteResource->shouldReceive('list')
        ->once()
        ->andThrow(new RuntimeException('Connection timeout'));

    $forgeClient = Mockery::mock(ForgeClient::class);
    $forgeClient->shouldReceive('sites')->andReturn($siteResource);

    app()->instance(ForgeClient::class, $forgeClient);

    $this->artisan('forge-test-branches:test-connection')
        ->expectsOutputToContain('Connection failed!')
        ->expectsOutputToContain('Connection timeout')
        ->assertFailed();
});
