<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\{DatabaseData, DatabaseUserData, SiteData};
use Ddr\ForgeTestBranches\Integrations\Forge\ForgeClient;
use Ddr\ForgeTestBranches\Integrations\Forge\Resources\{DatabaseResource, DatabaseUserResource, SiteResource};

beforeEach(function (): void {
    config([
        'forge-test-branches.server_id' => 12345,
        'forge-test-branches.forge_api_token' => 'test-token-1234567890',
        'forge-test-branches.git.repository' => 'user/repo',
    ]);
});

function makeSiteDataForConnection(int $id, string $name): SiteData
{
    return new SiteData(
        id: $id,
        serverId: 12345,
        name: $name,
        aliases: null,
        directory: '/public',
        wildcards: false,
        status: 'installed',
        repository: 'user/repo',
        repositoryProvider: 'gitlab',
        repositoryBranch: 'main',
        repositoryStatus: 'installed',
        quickDeploy: true,
        deploymentStatus: null,
        projectType: 'php',
        app: null,
        appStatus: null,
        hipchatRoom: null,
        slackChannel: null,
        telegramChatId: null,
        telegramChatTitle: null,
        teamsWebhookUrl: null,
        discordWebhookUrl: null,
        username: 'forge',
        balancingStatus: null,
        createdAt: now()->toDateTimeString(),
        deploymentUrl: null,
        isSecured: false,
        phpVersion: 'php84',
        tags: null,
        failureDeploymentEmails: null,
        telegramSecret: null,
        webDirectory: '/public',
    );
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
            new DatabaseUserData(id: 1, serverId: 12345, name: 'user1', status: 'installed', createdAt: now()->toDateTimeString(), databases: [1]),
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

test('exibe erro com dica para 404 quando conexão falha', function (): void {
    $siteResource = Mockery::mock(SiteResource::class);
    $siteResource->shouldReceive('list')
        ->once()
        ->andThrow(new RuntimeException('HTTP 404 Not Found'));

    $forgeClient = Mockery::mock(ForgeClient::class);
    $forgeClient->shouldReceive('sites')->andReturn($siteResource);

    app()->instance(ForgeClient::class, $forgeClient);

    $this->artisan('forge-test-branches:test-connection')
        ->expectsOutputToContain('Connection failed!')
        ->expectsOutputToContain('HTTP 404 Not Found')
        ->expectsOutputToContain('Possible causes:')
        ->expectsOutputToContain('Invalid FORGE_SERVER_ID')
        ->assertFailed();
});

test('exibe erro com dica para 401 quando token é inválido', function (): void {
    $siteResource = Mockery::mock(SiteResource::class);
    $siteResource->shouldReceive('list')
        ->once()
        ->andThrow(new RuntimeException('HTTP 401 Unauthorized'));

    $forgeClient = Mockery::mock(ForgeClient::class);
    $forgeClient->shouldReceive('sites')->andReturn($siteResource);

    app()->instance(ForgeClient::class, $forgeClient);

    $this->artisan('forge-test-branches:test-connection')
        ->expectsOutputToContain('Connection failed!')
        ->expectsOutputToContain('HTTP 401 Unauthorized')
        ->expectsOutputToContain('Possible causes:')
        ->expectsOutputToContain('Invalid FORGE_API_TOKEN')
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
