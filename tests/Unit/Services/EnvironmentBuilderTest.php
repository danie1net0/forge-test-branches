<?php

use Ddr\ForgeTestBranches\Data\{DatabaseData, DatabaseUserData, SiteData};
use Ddr\ForgeTestBranches\Services\{BranchSanitizer, DeploymentScriptBuilder, DomainBuilder, EnvironmentBuilder};
use Ddr\ForgeTestBranches\Integrations\Forge\Resources\{DatabaseResource, DatabaseUserResource, SiteResource};
use Ddr\ForgeTestBranches\Integrations\Forge\ForgeClient;
use Ddr\ForgeTestBranches\Models\ReviewEnvironment;

beforeEach(function (): void {
    config([
        'forge-test-branches.server_id' => 12345,
        'forge-test-branches.domain.base' => 'review.example.com',
        'forge-test-branches.domain.pattern' => '{branch}.{base}',
        'forge-test-branches.database.prefix' => 'review_',
        'forge-test-branches.site.project_type' => 'php',
        'forge-test-branches.site.directory' => '/public',
        'forge-test-branches.site.isolated' => false,
        'forge-test-branches.site.php_version' => 'php84',
        'forge-test-branches.git.provider' => 'gitlab',
        'forge-test-branches.git.repository' => 'user/repo',
        'forge-test-branches.deploy.script' => null,
        'forge-test-branches.deploy.quick_deploy' => true,
        'forge-test-branches.env_variables' => [],
    ]);
});

function makeSiteData(int $id, string $name): SiteData
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

test('creates complete environment successfully', function (): void {
    $databaseResource = Mockery::mock(DatabaseResource::class);
    $databaseUserResource = Mockery::mock(DatabaseUserResource::class);
    $siteResource = Mockery::mock(SiteResource::class);

    $databaseResource->shouldReceive('create')
        ->once()
        ->andReturn(new DatabaseData(id: 1, serverId: 12345, name: 'review_feat_hu_123', status: 'installed', createdAt: now()->toDateTimeString()));

    $databaseUserResource->shouldReceive('create')
        ->once()
        ->andReturn(new DatabaseUserData(id: 2, serverId: 12345, name: 'review_feat_hu_123', status: 'installed', createdAt: now()->toDateTimeString(), databases: [1]));

    $siteResource->shouldReceive('create')
        ->once()
        ->andReturn(makeSiteData(100, 'feat-hu-123.review.example.com'));

    $siteResource->shouldReceive('installGitRepository')
        ->once()
        ->andReturn(makeSiteData(100, 'feat-hu-123.review.example.com'));

    $siteResource->shouldReceive('getEnvironment')
        ->once()
        ->andReturn("APP_NAME=Laravel\nAPP_ENV=local");

    $siteResource->shouldReceive('updateEnvironment')->once();
    $siteResource->shouldReceive('updateDeploymentScript')->once();
    $siteResource->shouldReceive('enableQuickDeploy')->once();
    $siteResource->shouldReceive('deploy')->once();

    $forgeClient = Mockery::mock(ForgeClient::class);
    $forgeClient->shouldReceive('databases')->andReturn($databaseResource);
    $forgeClient->shouldReceive('databaseUsers')->andReturn($databaseUserResource);
    $forgeClient->shouldReceive('sites')->andReturn($siteResource);

    $builder = new EnvironmentBuilder(
        $forgeClient,
        new BranchSanitizer(),
        new DomainBuilder(),
        new DeploymentScriptBuilder(),
    );

    $environment = $builder->create('feat/hu-123');

    expect($environment)->toBeInstanceOf(ReviewEnvironment::class)
        ->and($environment->branch)->toBe('feat/hu-123')
        ->and($environment->slug)->toBe('feat-hu-123')
        ->and($environment->domain)->toBe('feat-hu-123.review.example.com')
        ->and($environment->server_id)->toBe(12345)
        ->and($environment->site_id)->toBe(100)
        ->and($environment->database_id)->toBe(1)
        ->and($environment->database_user_id)->toBe(2);
});

test('destroys environment removing resources in correct order', function (): void {
    $environment = ReviewEnvironment::query()->create([
        'branch' => 'feat/hu-456',
        'slug' => 'feat-hu-456',
        'domain' => 'feat-hu-456.review.example.com',
        'server_id' => 12345,
        'site_id' => 200,
        'database_id' => 10,
        'database_user_id' => 20,
    ]);

    $databaseResource = Mockery::mock(DatabaseResource::class);
    $databaseUserResource = Mockery::mock(DatabaseUserResource::class);
    $siteResource = Mockery::mock(SiteResource::class);

    $siteResource->shouldReceive('delete')->once()->with(12345, 200);
    $databaseUserResource->shouldReceive('delete')->once()->with(12345, 20);
    $databaseResource->shouldReceive('delete')->once()->with(12345, 10);

    $forgeClient = Mockery::mock(ForgeClient::class);
    $forgeClient->shouldReceive('databases')->andReturn($databaseResource);
    $forgeClient->shouldReceive('databaseUsers')->andReturn($databaseUserResource);
    $forgeClient->shouldReceive('sites')->andReturn($siteResource);

    $builder = new EnvironmentBuilder(
        $forgeClient,
        new BranchSanitizer(),
        new DomainBuilder(),
        new DeploymentScriptBuilder(),
    );

    $builder->destroy($environment);

    expect(ReviewEnvironment::query()->find($environment->id))->toBeNull();
});

test('deploys existing environment', function (): void {
    $environment = ReviewEnvironment::query()->create([
        'branch' => 'feat/hu-789',
        'slug' => 'feat-hu-789',
        'domain' => 'feat-hu-789.review.example.com',
        'server_id' => 12345,
        'site_id' => 300,
        'database_id' => 30,
        'database_user_id' => 40,
    ]);

    $siteResource = Mockery::mock(SiteResource::class);
    $siteResource->shouldReceive('deploy')->once()->with(12345, 300);

    $forgeClient = Mockery::mock(ForgeClient::class);
    $forgeClient->shouldReceive('sites')->andReturn($siteResource);

    $builder = new EnvironmentBuilder(
        $forgeClient,
        new BranchSanitizer(),
        new DomainBuilder(),
        new DeploymentScriptBuilder(),
    );

    $builder->deploy($environment);
});
