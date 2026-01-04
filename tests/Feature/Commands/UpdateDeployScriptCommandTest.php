<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\{SiteData};
use Ddr\ForgeTestBranches\Integrations\Forge\ForgeClient;
use Ddr\ForgeTestBranches\Integrations\Forge\Resources\{DatabaseResource, DatabaseUserResource, SiteResource};

beforeEach(function (): void {
    config([
        'forge-test-branches.server_id' => 12345,
        'forge-test-branches.domain.base' => 'review.example.com',
        'forge-test-branches.domain.pattern' => '{branch}.{base}',
        'forge-test-branches.database.prefix' => 'review_',
        'forge-test-branches.deploy.script' => null,
    ]);
});

test('atualiza script de deploy de ambiente existente', function (): void {
    $siteResource = Mockery::mock(SiteResource::class);
    $databaseResource = Mockery::mock(DatabaseResource::class);
    $databaseUserResource = Mockery::mock(DatabaseUserResource::class);

    $siteResource->shouldReceive('findByDomain')
        ->once()
        ->with(12345, 'feat-test.review.example.com')
        ->andReturn(new SiteData(
            id: 100,
            serverId: 12345,
            name: 'feat-test.review.example.com',
            aliases: null,
            directory: '/public',
            wildcards: false,
            status: 'installed',
            repository: 'user/repo',
            repositoryProvider: 'gitlab',
            repositoryBranch: 'feat/test',
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
        ));

    $databaseResource->shouldReceive('findByName')->andReturnNull();
    $databaseUserResource->shouldReceive('findByName')->andReturnNull();

    $siteResource->shouldReceive('updateDeploymentScript')
        ->once()
        ->withArgs(fn (int $serverId, int $siteId, string $script): bool => $serverId === 12345
                && $siteId === 100
                && str_contains($script, 'git fetch origin feat/test')
                && str_contains($script, 'git reset --hard origin/feat/test'));

    $forgeClient = Mockery::mock(ForgeClient::class);
    $forgeClient->shouldReceive('sites')->andReturn($siteResource);
    $forgeClient->shouldReceive('databases')->andReturn($databaseResource);
    $forgeClient->shouldReceive('databaseUsers')->andReturn($databaseUserResource);

    app()->instance(ForgeClient::class, $forgeClient);

    $this->artisan('forge-test-branches:update-script', ['--branch' => 'feat/test'])
        ->expectsOutput('Updating deploy script for branch: feat/test')
        ->expectsOutput('Deploy script updated successfully!')
        ->assertSuccessful();
});

test('falha quando branch não é especificada', function (): void {
    config(['forge-test-branches.forge_api_token' => 'test-token']);

    $this->artisan('forge-test-branches:update-script')
        ->expectsOutput('Branch not specified. Use --branch=branch-name or set CI_COMMIT_REF_NAME')
        ->assertFailed();
});

test('falha quando ambiente não existe', function (): void {
    $siteResource = Mockery::mock(SiteResource::class);
    $siteResource->shouldReceive('findByDomain')
        ->once()
        ->andReturnNull();

    $forgeClient = Mockery::mock(ForgeClient::class);
    $forgeClient->shouldReceive('sites')->andReturn($siteResource);

    app()->instance(ForgeClient::class, $forgeClient);

    $this->artisan('forge-test-branches:update-script', ['--branch' => 'feat/nonexistent'])
        ->expectsOutput('Environment not found for branch: feat/nonexistent')
        ->assertFailed();
});
