<?php

declare(strict_types=1);

use Illuminate\Support\Sleep;
use Ddr\ForgeTestBranches\Data\CertificateData;
use Ddr\ForgeTestBranches\Data\{CreateDatabaseData, CreateDatabaseUserData, DatabaseData, DatabaseUserData, EnvironmentData, SiteData};
use Ddr\ForgeTestBranches\Integrations\Forge\ForgeClient;
use Ddr\ForgeTestBranches\Integrations\Forge\Resources\{DatabaseResource, DatabaseUserResource, SiteResource};
use Ddr\ForgeTestBranches\Logger;
use Ddr\ForgeTestBranches\Services\{BranchSanitizer, DeploymentScriptBuilder, DomainBuilder, EnvironmentBuilder};

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
        'forge-test-branches.ssl.enabled' => false,
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

function makeEnvironmentBuilder(ForgeClient $forgeClient): EnvironmentBuilder
{
    return new EnvironmentBuilder(
        $forgeClient,
        new BranchSanitizer(),
        new DomainBuilder(),
        new DeploymentScriptBuilder(),
        Mockery::mock(Logger::class)->shouldIgnoreMissing(),
    );
}

test('cria ambiente completo com sucesso', function (): void {
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

    $siteResource->shouldReceive('waitForRepositoryInstallation')
        ->once()
        ->andReturn(makeSiteData(100, 'feat-hu-123.review.example.com'));

    $siteResource->shouldReceive('get')
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

    $builder = makeEnvironmentBuilder($forgeClient);
    $environment = $builder->create('feat/hu-123');

    expect($environment)->toBeInstanceOf(EnvironmentData::class)
        ->branch->toBe('feat/hu-123')
        ->slug->toBe('feat-hu-123')
        ->domain->toBe('feat-hu-123.review.example.com')
        ->serverId->toBe(12345)
        ->siteId->toBe(100)
        ->databaseId->toBe(1)
        ->databaseUserId->toBe(2);
});

test('encontra ambiente existente via Forge API', function (): void {
    $databaseResource = Mockery::mock(DatabaseResource::class);
    $databaseUserResource = Mockery::mock(DatabaseUserResource::class);
    $siteResource = Mockery::mock(SiteResource::class);

    $siteResource->shouldReceive('findByDomain')
        ->once()
        ->with(12345, 'feat-hu-456.review.example.com')
        ->andReturn(makeSiteData(200, 'feat-hu-456.review.example.com'));

    $databaseResource->shouldReceive('findByName')
        ->once()
        ->with(12345, 'review_feat_hu_456')
        ->andReturn(new DatabaseData(id: 10, serverId: 12345, name: 'review_feat_hu_456', status: 'installed', createdAt: now()->toDateTimeString()));

    $databaseUserResource->shouldReceive('findByName')
        ->once()
        ->with(12345, 'review_feat_hu_456')
        ->andReturn(new DatabaseUserData(id: 20, serverId: 12345, name: 'review_feat_hu_456', status: 'installed', createdAt: now()->toDateTimeString(), databases: [10]));

    $forgeClient = Mockery::mock(ForgeClient::class);
    $forgeClient->shouldReceive('databases')->andReturn($databaseResource);
    $forgeClient->shouldReceive('databaseUsers')->andReturn($databaseUserResource);
    $forgeClient->shouldReceive('sites')->andReturn($siteResource);

    $builder = makeEnvironmentBuilder($forgeClient);
    $environment = $builder->find('feat/hu-456');

    expect($environment)->not->toBeNull()
        ->branch->toBe('feat/hu-456')
        ->siteId->toBe(200)
        ->databaseId->toBe(10)
        ->databaseUserId->toBe(20);
});

test('retorna null quando site não existe', function (): void {
    $siteResource = Mockery::mock(SiteResource::class);
    $siteResource->shouldReceive('findByDomain')
        ->once()
        ->with(12345, 'feat-nonexistent.review.example.com')
        ->andReturnNull();

    $forgeClient = Mockery::mock(ForgeClient::class);
    $forgeClient->shouldReceive('sites')->andReturn($siteResource);

    $builder = makeEnvironmentBuilder($forgeClient);
    $environment = $builder->find('feat/nonexistent');

    expect($environment)->toBeNull();
});

test('verifica que ambiente existe', function (): void {
    $siteResource = Mockery::mock(SiteResource::class);
    $siteResource->shouldReceive('findByDomain')
        ->once()
        ->with(12345, 'feat-exists.review.example.com')
        ->andReturn(makeSiteData(100, 'feat-exists.review.example.com'));

    $databaseResource = Mockery::mock(DatabaseResource::class);
    $databaseResource->shouldReceive('findByName')->andReturnNull();

    $databaseUserResource = Mockery::mock(DatabaseUserResource::class);
    $databaseUserResource->shouldReceive('findByName')->andReturnNull();

    $forgeClient = Mockery::mock(ForgeClient::class);
    $forgeClient->shouldReceive('sites')->andReturn($siteResource);
    $forgeClient->shouldReceive('databases')->andReturn($databaseResource);
    $forgeClient->shouldReceive('databaseUsers')->andReturn($databaseUserResource);

    $builder = makeEnvironmentBuilder($forgeClient);

    expect($builder->exists('feat/exists'))->toBeTrue();
});

test('verifica que ambiente não existe', function (): void {
    $siteResource = Mockery::mock(SiteResource::class);
    $siteResource->shouldReceive('findByDomain')
        ->once()
        ->andReturnNull();

    $forgeClient = Mockery::mock(ForgeClient::class);
    $forgeClient->shouldReceive('sites')->andReturn($siteResource);

    $builder = makeEnvironmentBuilder($forgeClient);

    expect($builder->exists('feat/not-exists'))->toBeFalse();
});

test('destrói ambiente removendo recursos na ordem correta', function (): void {
    $environment = new EnvironmentData(
        branch: 'feat/hu-456',
        slug: 'feat-hu-456',
        domain: 'feat-hu-456.review.example.com',
        serverId: 12345,
        siteId: 200,
        databaseId: 10,
        databaseUserId: 20,
    );

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

    $builder = makeEnvironmentBuilder($forgeClient);
    $builder->destroy($environment);
});

test('destrói ambiente sem database quando não existe', function (): void {
    $environment = new EnvironmentData(
        branch: 'feat/no-db',
        slug: 'feat-no-db',
        domain: 'feat-no-db.review.example.com',
        serverId: 12345,
        siteId: 300,
    );

    $siteResource = Mockery::mock(SiteResource::class);
    $siteResource->shouldReceive('delete')->once()->with(12345, 300);

    $forgeClient = Mockery::mock(ForgeClient::class);
    $forgeClient->shouldReceive('sites')->andReturn($siteResource);

    $builder = makeEnvironmentBuilder($forgeClient);
    $builder->destroy($environment);
});

test('executa deploy de ambiente existente', function (): void {
    $environment = new EnvironmentData(
        branch: 'feat/hu-789',
        slug: 'feat-hu-789',
        domain: 'feat-hu-789.review.example.com',
        serverId: 12345,
        siteId: 300,
        databaseId: 30,
        databaseUserId: 40,
    );

    $siteResource = Mockery::mock(SiteResource::class);
    $siteResource->shouldReceive('deploy')->once()->with(12345, 300);

    $forgeClient = Mockery::mock(ForgeClient::class);
    $forgeClient->shouldReceive('sites')->andReturn($siteResource);

    $builder = makeEnvironmentBuilder($forgeClient);
    $builder->deploy($environment);
});

test('envia dados corretos para criação de usuário de database', function (): void {
    $capturedData = null;

    $databaseResource = Mockery::mock(DatabaseResource::class);
    $databaseUserResource = Mockery::mock(DatabaseUserResource::class);
    $siteResource = Mockery::mock(SiteResource::class);

    $databaseResource->shouldReceive('create')
        ->once()
        ->andReturn(new DatabaseData(id: 1, serverId: 12345, name: 'review_feat_test', status: 'installed', createdAt: now()->toDateTimeString()));

    $databaseUserResource->shouldReceive('create')
        ->once()
        ->withArgs(function (int $serverId, CreateDatabaseUserData $data) use (&$capturedData): bool {
            $capturedData = $data->toArray();

            return true;
        })
        ->andReturn(new DatabaseUserData(id: 2, serverId: 12345, name: 'review_feat_test', status: 'installed', createdAt: now()->toDateTimeString(), databases: [1]));

    $siteResource->shouldReceive('create')
        ->once()
        ->andReturn(makeSiteData(100, 'feat-test.review.example.com'));

    $siteResource->shouldReceive('installGitRepository')->once()->andReturn(makeSiteData(100, 'feat-test.review.example.com'));
    $siteResource->shouldReceive('waitForRepositoryInstallation')->once()->andReturn(makeSiteData(100, 'feat-test.review.example.com'));
    $siteResource->shouldReceive('get')->andReturn(makeSiteData(100, 'feat-test.review.example.com'));
    $siteResource->shouldReceive('getEnvironment')->once()->andReturn("APP_NAME=Laravel\nAPP_ENV=local");
    $siteResource->shouldReceive('updateEnvironment')->once();
    $siteResource->shouldReceive('updateDeploymentScript')->once();
    $siteResource->shouldReceive('enableQuickDeploy')->once();
    $siteResource->shouldReceive('deploy')->once();

    $forgeClient = Mockery::mock(ForgeClient::class);
    $forgeClient->shouldReceive('databases')->andReturn($databaseResource);
    $forgeClient->shouldReceive('databaseUsers')->andReturn($databaseUserResource);
    $forgeClient->shouldReceive('sites')->andReturn($siteResource);

    $builder = makeEnvironmentBuilder($forgeClient);
    $builder->create('feat/test');

    expect($capturedData)
        ->toHaveKey('name')
        ->toHaveKey('password')
        ->toHaveKey('databases')
        ->and($capturedData['name'])->toBe('review_feat_test')
        ->and($capturedData['password'])->toMatch('/^[a-zA-Z0-9]+$/')
        ->and(mb_strlen((string) $capturedData['password']))->toBe(32)
        ->and($capturedData['databases'])->toBe([1]);
});

test('trunca nome do database respeitando limite de 32 caracteres', function (): void {
    $capturedDbData = null;
    $capturedUserData = null;

    $databaseResource = Mockery::mock(DatabaseResource::class);
    $databaseUserResource = Mockery::mock(DatabaseUserResource::class);
    $siteResource = Mockery::mock(SiteResource::class);

    $databaseResource->shouldReceive('create')
        ->once()
        ->withArgs(function (int $serverId, CreateDatabaseData $data) use (&$capturedDbData): bool {
            $capturedDbData = $data->toArray();

            return true;
        })
        ->andReturn(new DatabaseData(id: 1, serverId: 12345, name: 'review_sprint_16_feature_t_abc123', status: 'installed', createdAt: now()->toDateTimeString()));

    $databaseUserResource->shouldReceive('create')
        ->once()
        ->withArgs(function (int $serverId, CreateDatabaseUserData $data) use (&$capturedUserData): bool {
            $capturedUserData = $data->toArray();

            return true;
        })
        ->andReturn(new DatabaseUserData(id: 2, serverId: 12345, name: 'review_sprint_16_feature_t_abc123', status: 'installed', createdAt: now()->toDateTimeString(), databases: [1]));

    $siteResource->shouldReceive('create')
        ->once()
        ->andReturn(makeSiteData(100, 'sprint-16-feature-test-branches.review.example.com'));

    $siteResource->shouldReceive('installGitRepository')->once()->andReturn(makeSiteData(100, 'sprint-16-feature-test-branches.review.example.com'));
    $siteResource->shouldReceive('waitForRepositoryInstallation')->once()->andReturn(makeSiteData(100, 'sprint-16-feature-test-branches.review.example.com'));
    $siteResource->shouldReceive('get')->andReturn(makeSiteData(100, 'sprint-16-feature-test-branches.review.example.com'));
    $siteResource->shouldReceive('getEnvironment')->once()->andReturn("APP_NAME=Laravel\nAPP_ENV=local");
    $siteResource->shouldReceive('updateEnvironment')->once();
    $siteResource->shouldReceive('updateDeploymentScript')->once();
    $siteResource->shouldReceive('enableQuickDeploy')->once();
    $siteResource->shouldReceive('deploy')->once();

    $forgeClient = Mockery::mock(ForgeClient::class);
    $forgeClient->shouldReceive('databases')->andReturn($databaseResource);
    $forgeClient->shouldReceive('databaseUsers')->andReturn($databaseUserResource);
    $forgeClient->shouldReceive('sites')->andReturn($siteResource);

    $builder = makeEnvironmentBuilder($forgeClient);
    $builder->create('sprint-16/feature/test-branches');

    expect(mb_strlen((string) $capturedDbData['name']))->toBeLessThanOrEqual(32)
        ->and($capturedDbData['name'])->toStartWith('review_')
        ->toMatch('/^review_sprint_16_feature_[a-z0-9_]+$/')
        ->and($capturedUserData['name'])->toBe($capturedDbData['name']);
});

test('processa placeholders {slug} e {env:VAR} nas variáveis de ambiente', function (): void {
    putenv('BASE_APP_KEY=key123');

    config([
        'forge-test-branches.env_variables' => [
            'APP_URL' => 'https://{slug}.review.example.com',
            'APP_KEY' => '{env:BASE_APP_KEY}',
        ],
    ]);

    $capturedEnv = null;

    $databaseResource = Mockery::mock(DatabaseResource::class);
    $databaseUserResource = Mockery::mock(DatabaseUserResource::class);
    $siteResource = Mockery::mock(SiteResource::class);

    $databaseResource->shouldReceive('create')->once()->andReturn(new DatabaseData(id: 1, serverId: 12345, name: 'review_test', status: 'installed', createdAt: now()->toDateTimeString()));
    $databaseUserResource->shouldReceive('create')->once()->andReturn(new DatabaseUserData(id: 2, serverId: 12345, name: 'review_test', status: 'installed', createdAt: now()->toDateTimeString(), databases: [1]));
    $siteResource->shouldReceive('create')->once()->andReturn(makeSiteData(100, 'test.review.example.com'));
    $siteResource->shouldReceive('installGitRepository')->once()->andReturn(makeSiteData(100, 'test.review.example.com'));
    $siteResource->shouldReceive('waitForRepositoryInstallation')->once()->andReturn(makeSiteData(100, 'test.review.example.com'));
    $siteResource->shouldReceive('get')->andReturn(makeSiteData(100, 'test.review.example.com'));
    $siteResource->shouldReceive('getEnvironment')->once()->andReturn('APP_NAME=Laravel');
    $siteResource->shouldReceive('updateEnvironment')
        ->once()
        ->withArgs(function (int $serverId, int $siteId, string $content) use (&$capturedEnv): bool {
            $capturedEnv = $content;

            return true;
        });
    $siteResource->shouldReceive('updateDeploymentScript')->once();
    $siteResource->shouldReceive('enableQuickDeploy')->once();
    $siteResource->shouldReceive('deploy')->once();

    $forgeClient = Mockery::mock(ForgeClient::class);
    $forgeClient->shouldReceive('databases')->andReturn($databaseResource);
    $forgeClient->shouldReceive('databaseUsers')->andReturn($databaseUserResource);
    $forgeClient->shouldReceive('sites')->andReturn($siteResource);

    $builder = makeEnvironmentBuilder($forgeClient);
    $builder->create('test');

    expect($capturedEnv)->toContain('APP_URL=https://test.review.example.com')
        ->toContain('APP_KEY=key123');

    putenv('BASE_APP_KEY');
});

test('lista todos os ambientes de review do servidor', function (): void {
    $siteResource = Mockery::mock(SiteResource::class);
    $siteResource->shouldReceive('list')
        ->once()
        ->with(12345)
        ->andReturn([
            makeSiteData(100, 'feat-login.review.example.com'),
            makeSiteData(200, 'fix-bug.review.example.com'),
            makeSiteData(300, 'production.other-domain.com'),
        ]);

    $forgeClient = Mockery::mock(ForgeClient::class);
    $forgeClient->shouldReceive('sites')->andReturn($siteResource);

    $builder = makeEnvironmentBuilder($forgeClient);
    $environments = $builder->listAll();

    expect($environments)->toHaveCount(2)
        ->and($environments[0])->toBeInstanceOf(EnvironmentData::class)
        ->siteId->toBe(100)
        ->domain->toBe('feat-login.review.example.com')
        ->and($environments[1])->toBeInstanceOf(EnvironmentData::class)
        ->siteId->toBe(200)
        ->domain->toBe('fix-bug.review.example.com');
});

test('retorna array vazio quando não há ambientes de review', function (): void {
    $siteResource = Mockery::mock(SiteResource::class);
    $siteResource->shouldReceive('list')
        ->once()
        ->with(12345)
        ->andReturn([
            makeSiteData(100, 'production.other-domain.com'),
        ]);

    $forgeClient = Mockery::mock(ForgeClient::class);
    $forgeClient->shouldReceive('sites')->andReturn($siteResource);

    $builder = makeEnvironmentBuilder($forgeClient);

    expect($builder->listAll())->toBeEmpty();
});

test('usa repositoryBranch do site como branch do ambiente', function (): void {
    $site = makeSiteData(100, 'feat-login.review.example.com');

    $siteResource = Mockery::mock(SiteResource::class);
    $siteResource->shouldReceive('list')
        ->once()
        ->andReturn([$site]);

    $forgeClient = Mockery::mock(ForgeClient::class);
    $forgeClient->shouldReceive('sites')->andReturn($siteResource);

    $builder = makeEnvironmentBuilder($forgeClient);
    $environments = $builder->listAll();

    expect($environments[0]->branch)->toBe('main');
});

test('cria ambiente com certificado SSL quando habilitado', function (): void {
    config(['forge-test-branches.ssl.enabled' => true]);

    $databaseResource = Mockery::mock(DatabaseResource::class);
    $databaseUserResource = Mockery::mock(DatabaseUserResource::class);
    $siteResource = Mockery::mock(SiteResource::class);

    $databaseResource->shouldReceive('create')->once()
        ->andReturn(new DatabaseData(id: 1, serverId: 12345, name: 'review_feat_ssl', status: 'installed', createdAt: now()->toDateTimeString()));
    $databaseUserResource->shouldReceive('create')->once()
        ->andReturn(new DatabaseUserData(id: 2, serverId: 12345, name: 'review_feat_ssl', status: 'installed', createdAt: now()->toDateTimeString(), databases: [1]));
    $siteResource->shouldReceive('create')->once()->andReturn(makeSiteData(100, 'feat-ssl.review.example.com'));
    $siteResource->shouldReceive('installGitRepository')->once()->andReturn(makeSiteData(100, 'feat-ssl.review.example.com'));
    $siteResource->shouldReceive('waitForRepositoryInstallation')->once()->andReturn(makeSiteData(100, 'feat-ssl.review.example.com'));
    $siteResource->shouldReceive('get')->andReturn(makeSiteData(100, 'feat-ssl.review.example.com'));
    $siteResource->shouldReceive('getEnvironment')->once()->andReturn('APP_NAME=Laravel');
    $siteResource->shouldReceive('updateEnvironment')->once();
    $siteResource->shouldReceive('updateDeploymentScript')->once();
    $siteResource->shouldReceive('enableQuickDeploy')->once();
    $siteResource->shouldReceive('obtainLetsEncryptCertificate')->once()
        ->andReturn(new CertificateData(id: 1, serverId: 12345, siteId: 100, domains: ['feat-ssl.review.example.com'], requestStatus: 'created', status: 'installed', existing: false, active: true, createdAt: now()->toDateTimeString(), activatedAt: null));
    $siteResource->shouldReceive('waitForCertificateActivation')->once()
        ->andReturn(new CertificateData(id: 1, serverId: 12345, siteId: 100, domains: ['feat-ssl.review.example.com'], requestStatus: 'created', status: 'installed', existing: false, active: true, createdAt: now()->toDateTimeString(), activatedAt: now()->toDateTimeString()));
    $siteResource->shouldReceive('deploy')->once();

    $forgeClient = Mockery::mock(ForgeClient::class);
    $forgeClient->shouldReceive('databases')->andReturn($databaseResource);
    $forgeClient->shouldReceive('databaseUsers')->andReturn($databaseUserResource);
    $forgeClient->shouldReceive('sites')->andReturn($siteResource);

    $builder = makeEnvironmentBuilder($forgeClient);
    $environment = $builder->create('feat/ssl');

    expect($environment)->toBeInstanceOf(EnvironmentData::class)
        ->domain->toBe('feat-ssl.review.example.com');
});

test('não habilita quick deploy quando desabilitado', function (): void {
    config(['forge-test-branches.deploy.quick_deploy' => false]);

    $databaseResource = Mockery::mock(DatabaseResource::class);
    $databaseUserResource = Mockery::mock(DatabaseUserResource::class);
    $siteResource = Mockery::mock(SiteResource::class);

    $databaseResource->shouldReceive('create')->once()
        ->andReturn(new DatabaseData(id: 1, serverId: 12345, name: 'review_feat_no_qd', status: 'installed', createdAt: now()->toDateTimeString()));
    $databaseUserResource->shouldReceive('create')->once()
        ->andReturn(new DatabaseUserData(id: 2, serverId: 12345, name: 'review_feat_no_qd', status: 'installed', createdAt: now()->toDateTimeString(), databases: [1]));
    $siteResource->shouldReceive('create')->once()->andReturn(makeSiteData(100, 'feat-no-qd.review.example.com'));
    $siteResource->shouldReceive('installGitRepository')->once()->andReturn(makeSiteData(100, 'feat-no-qd.review.example.com'));
    $siteResource->shouldReceive('waitForRepositoryInstallation')->once()->andReturn(makeSiteData(100, 'feat-no-qd.review.example.com'));
    $siteResource->shouldReceive('get')->andReturn(makeSiteData(100, 'feat-no-qd.review.example.com'));
    $siteResource->shouldReceive('getEnvironment')->once()->andReturn('APP_NAME=Laravel');
    $siteResource->shouldReceive('updateEnvironment')->once();
    $siteResource->shouldReceive('updateDeploymentScript')->once();
    $siteResource->shouldNotReceive('enableQuickDeploy');
    $siteResource->shouldReceive('deploy')->once();

    $forgeClient = Mockery::mock(ForgeClient::class);
    $forgeClient->shouldReceive('databases')->andReturn($databaseResource);
    $forgeClient->shouldReceive('databaseUsers')->andReturn($databaseUserResource);
    $forgeClient->shouldReceive('sites')->andReturn($siteResource);

    $builder = makeEnvironmentBuilder($forgeClient);
    $environment = $builder->create('feat/no-qd');

    expect($environment)->toBeInstanceOf(EnvironmentData::class)
        ->domain->toBe('feat-no-qd.review.example.com');
});

test('faz rollback ao falhar na criação do ambiente', function (): void {
    $databaseResource = Mockery::mock(DatabaseResource::class);
    $databaseUserResource = Mockery::mock(DatabaseUserResource::class);
    $siteResource = Mockery::mock(SiteResource::class);

    $databaseResource->shouldReceive('create')->once()
        ->andReturn(new DatabaseData(id: 1, serverId: 12345, name: 'review_feat_fail', status: 'installed', createdAt: now()->toDateTimeString()));
    $databaseUserResource->shouldReceive('create')->once()
        ->andReturn(new DatabaseUserData(id: 2, serverId: 12345, name: 'review_feat_fail', status: 'installed', createdAt: now()->toDateTimeString(), databases: [1]));
    $siteResource->shouldReceive('create')->once()
        ->andReturn(makeSiteData(100, 'feat-fail.review.example.com'));
    $siteResource->shouldReceive('installGitRepository')->once()
        ->andThrow(new RuntimeException('Git install failed'));

    $siteResource->shouldReceive('delete')->once()->with(12345, 100);
    $databaseUserResource->shouldReceive('delete')->once()->with(12345, 2);
    $databaseResource->shouldReceive('delete')->once()->with(12345, 1);

    $forgeClient = Mockery::mock(ForgeClient::class);
    $forgeClient->shouldReceive('databases')->andReturn($databaseResource);
    $forgeClient->shouldReceive('databaseUsers')->andReturn($databaseUserResource);
    $forgeClient->shouldReceive('sites')->andReturn($siteResource);

    $builder = makeEnvironmentBuilder($forgeClient);

    expect(fn (): EnvironmentData => $builder->create('feat/fail'))
        ->toThrow(RuntimeException::class, 'Git install failed');
});

test('rollback trata erros individuais sem propagar', function (): void {
    $databaseResource = Mockery::mock(DatabaseResource::class);
    $databaseUserResource = Mockery::mock(DatabaseUserResource::class);
    $siteResource = Mockery::mock(SiteResource::class);

    $databaseResource->shouldReceive('create')->once()
        ->andReturn(new DatabaseData(id: 1, serverId: 12345, name: 'review_feat_rb', status: 'installed', createdAt: now()->toDateTimeString()));
    $databaseUserResource->shouldReceive('create')->once()
        ->andReturn(new DatabaseUserData(id: 2, serverId: 12345, name: 'review_feat_rb', status: 'installed', createdAt: now()->toDateTimeString(), databases: [1]));
    $siteResource->shouldReceive('create')->once()
        ->andReturn(makeSiteData(100, 'feat-rb.review.example.com'));
    $siteResource->shouldReceive('installGitRepository')->once()
        ->andThrow(new RuntimeException('Git install failed'));

    $siteResource->shouldReceive('delete')->once()->andThrow(new RuntimeException('Site delete failed'));
    $databaseUserResource->shouldReceive('delete')->once()->andThrow(new RuntimeException('User delete failed'));
    $databaseResource->shouldReceive('delete')->once()->andThrow(new RuntimeException('DB delete failed'));

    $forgeClient = Mockery::mock(ForgeClient::class);
    $forgeClient->shouldReceive('databases')->andReturn($databaseResource);
    $forgeClient->shouldReceive('databaseUsers')->andReturn($databaseUserResource);
    $forgeClient->shouldReceive('sites')->andReturn($siteResource);

    $builder = makeEnvironmentBuilder($forgeClient);

    expect(fn (): EnvironmentData => $builder->create('feat/rb'))
        ->toThrow(RuntimeException::class, 'Git install failed');
});

test('rollback ignora recursos null', function (): void {
    $databaseResource = Mockery::mock(DatabaseResource::class);
    $databaseUserResource = Mockery::mock(DatabaseUserResource::class);
    $siteResource = Mockery::mock(SiteResource::class);

    $databaseResource->shouldReceive('create')->once()
        ->andThrow(new RuntimeException('Database creation failed'));

    $siteResource->shouldNotReceive('delete');
    $databaseUserResource->shouldNotReceive('delete');
    $databaseResource->shouldNotReceive('delete');

    $forgeClient = Mockery::mock(ForgeClient::class);
    $forgeClient->shouldReceive('databases')->andReturn($databaseResource);
    $forgeClient->shouldReceive('databaseUsers')->andReturn($databaseUserResource);
    $forgeClient->shouldReceive('sites')->andReturn($siteResource);

    $builder = makeEnvironmentBuilder($forgeClient);

    expect(fn (): EnvironmentData => $builder->create('feat/fail-early'))
        ->toThrow(RuntimeException::class, 'Database creation failed');
});

test('lança exceção de destruição parcial quando falha ao deletar site', function (): void {
    $environment = new EnvironmentData(
        branch: 'feat/partial',
        slug: 'feat-partial',
        domain: 'feat-partial.review.example.com',
        serverId: 12345,
        siteId: 200,
        databaseId: 10,
        databaseUserId: 20,
    );

    $siteResource = Mockery::mock(SiteResource::class);
    $databaseResource = Mockery::mock(DatabaseResource::class);
    $databaseUserResource = Mockery::mock(DatabaseUserResource::class);

    $siteResource->shouldReceive('delete')->once()->andThrow(new RuntimeException('Site delete error'));
    $databaseUserResource->shouldReceive('delete')->once()->with(12345, 20);
    $databaseResource->shouldReceive('delete')->once()->with(12345, 10);

    $forgeClient = Mockery::mock(ForgeClient::class);
    $forgeClient->shouldReceive('sites')->andReturn($siteResource);
    $forgeClient->shouldReceive('databases')->andReturn($databaseResource);
    $forgeClient->shouldReceive('databaseUsers')->andReturn($databaseUserResource);

    $builder = makeEnvironmentBuilder($forgeClient);

    expect(fn (): null => $builder->destroy($environment) ?? null)
        ->toThrow(RuntimeException::class, 'Partial destruction');
});

test('lança exceção de destruição parcial quando falha ao deletar database user', function (): void {
    $environment = new EnvironmentData(
        branch: 'feat/partial-user',
        slug: 'feat-partial-user',
        domain: 'feat-partial-user.review.example.com',
        serverId: 12345,
        siteId: 200,
        databaseId: 10,
        databaseUserId: 20,
    );

    $siteResource = Mockery::mock(SiteResource::class);
    $databaseResource = Mockery::mock(DatabaseResource::class);
    $databaseUserResource = Mockery::mock(DatabaseUserResource::class);

    $siteResource->shouldReceive('delete')->once();
    $databaseUserResource->shouldReceive('delete')->once()->andThrow(new RuntimeException('User delete error'));
    $databaseResource->shouldReceive('delete')->once();

    $forgeClient = Mockery::mock(ForgeClient::class);
    $forgeClient->shouldReceive('sites')->andReturn($siteResource);
    $forgeClient->shouldReceive('databases')->andReturn($databaseResource);
    $forgeClient->shouldReceive('databaseUsers')->andReturn($databaseUserResource);

    $builder = makeEnvironmentBuilder($forgeClient);

    expect(fn (): null => $builder->destroy($environment) ?? null)
        ->toThrow(RuntimeException::class, 'Partial destruction');
});

test('lança exceção de destruição parcial quando falha ao deletar database', function (): void {
    $environment = new EnvironmentData(
        branch: 'feat/partial-db',
        slug: 'feat-partial-db',
        domain: 'feat-partial-db.review.example.com',
        serverId: 12345,
        siteId: 200,
        databaseId: 10,
        databaseUserId: 20,
    );

    $siteResource = Mockery::mock(SiteResource::class);
    $databaseResource = Mockery::mock(DatabaseResource::class);
    $databaseUserResource = Mockery::mock(DatabaseUserResource::class);

    $siteResource->shouldReceive('delete')->once();
    $databaseUserResource->shouldReceive('delete')->once();
    $databaseResource->shouldReceive('delete')->once()->andThrow(new RuntimeException('DB delete error'));

    $forgeClient = Mockery::mock(ForgeClient::class);
    $forgeClient->shouldReceive('sites')->andReturn($siteResource);
    $forgeClient->shouldReceive('databases')->andReturn($databaseResource);
    $forgeClient->shouldReceive('databaseUsers')->andReturn($databaseUserResource);

    $builder = makeEnvironmentBuilder($forgeClient);

    expect(fn (): null => $builder->destroy($environment) ?? null)
        ->toThrow(RuntimeException::class, 'Partial destruction');
});

test('retorna array vazio quando env_variables não é array', function (): void {
    config(['forge-test-branches.env_variables' => 'not-an-array']);

    $capturedEnv = null;

    $databaseResource = Mockery::mock(DatabaseResource::class);
    $databaseUserResource = Mockery::mock(DatabaseUserResource::class);
    $siteResource = Mockery::mock(SiteResource::class);

    $databaseResource->shouldReceive('create')->once()
        ->andReturn(new DatabaseData(id: 1, serverId: 12345, name: 'review_feat_env', status: 'installed', createdAt: now()->toDateTimeString()));
    $databaseUserResource->shouldReceive('create')->once()
        ->andReturn(new DatabaseUserData(id: 2, serverId: 12345, name: 'review_feat_env', status: 'installed', createdAt: now()->toDateTimeString(), databases: [1]));
    $siteResource->shouldReceive('create')->once()->andReturn(makeSiteData(100, 'feat-env.review.example.com'));
    $siteResource->shouldReceive('installGitRepository')->once()->andReturn(makeSiteData(100, 'feat-env.review.example.com'));
    $siteResource->shouldReceive('waitForRepositoryInstallation')->once()->andReturn(makeSiteData(100, 'feat-env.review.example.com'));
    $siteResource->shouldReceive('get')->andReturn(makeSiteData(100, 'feat-env.review.example.com'));
    $siteResource->shouldReceive('getEnvironment')->once()->andReturn('APP_NAME=Laravel');
    $siteResource->shouldReceive('updateEnvironment')
        ->once()
        ->withArgs(function (int $serverId, int $siteId, string $content) use (&$capturedEnv): bool {
            $capturedEnv = $content;

            return true;
        });
    $siteResource->shouldReceive('updateDeploymentScript')->once();
    $siteResource->shouldReceive('enableQuickDeploy')->once();
    $siteResource->shouldReceive('deploy')->once();

    $forgeClient = Mockery::mock(ForgeClient::class);
    $forgeClient->shouldReceive('databases')->andReturn($databaseResource);
    $forgeClient->shouldReceive('databaseUsers')->andReturn($databaseUserResource);
    $forgeClient->shouldReceive('sites')->andReturn($siteResource);

    $builder = makeEnvironmentBuilder($forgeClient);
    $builder->create('feat/env');

    expect($capturedEnv)->not->toContain('not-an-array');
});

test('processa placeholder {env:VAR} com valor null retornando vazio', function (): void {
    putenv('NONEXISTENT_VAR');

    config([
        'forge-test-branches.env_variables' => [
            'CUSTOM_VAR' => '{env:NONEXISTENT_VAR}',
        ],
    ]);

    $capturedEnv = null;

    $databaseResource = Mockery::mock(DatabaseResource::class);
    $databaseUserResource = Mockery::mock(DatabaseUserResource::class);
    $siteResource = Mockery::mock(SiteResource::class);

    $databaseResource->shouldReceive('create')->once()
        ->andReturn(new DatabaseData(id: 1, serverId: 12345, name: 'review_feat_null', status: 'installed', createdAt: now()->toDateTimeString()));
    $databaseUserResource->shouldReceive('create')->once()
        ->andReturn(new DatabaseUserData(id: 2, serverId: 12345, name: 'review_feat_null', status: 'installed', createdAt: now()->toDateTimeString(), databases: [1]));
    $siteResource->shouldReceive('create')->once()->andReturn(makeSiteData(100, 'feat-null.review.example.com'));
    $siteResource->shouldReceive('installGitRepository')->once()->andReturn(makeSiteData(100, 'feat-null.review.example.com'));
    $siteResource->shouldReceive('waitForRepositoryInstallation')->once()->andReturn(makeSiteData(100, 'feat-null.review.example.com'));
    $siteResource->shouldReceive('get')->andReturn(makeSiteData(100, 'feat-null.review.example.com'));
    $siteResource->shouldReceive('getEnvironment')->once()->andReturn('APP_NAME=Laravel');
    $siteResource->shouldReceive('updateEnvironment')
        ->once()
        ->withArgs(function (int $serverId, int $siteId, string $content) use (&$capturedEnv): bool {
            $capturedEnv = $content;

            return true;
        });
    $siteResource->shouldReceive('updateDeploymentScript')->once();
    $siteResource->shouldReceive('enableQuickDeploy')->once();
    $siteResource->shouldReceive('deploy')->once();

    $forgeClient = Mockery::mock(ForgeClient::class);
    $forgeClient->shouldReceive('databases')->andReturn($databaseResource);
    $forgeClient->shouldReceive('databaseUsers')->andReturn($databaseUserResource);
    $forgeClient->shouldReceive('sites')->andReturn($siteResource);

    $builder = makeEnvironmentBuilder($forgeClient);
    $builder->create('feat/null');

    expect($capturedEnv)->toContain('CUSTOM_VAR=');
});

test('waitForForgeProvisioning expira e continua normalmente', function (): void {
    Sleep::fake();

    $databaseResource = Mockery::mock(DatabaseResource::class);
    $databaseUserResource = Mockery::mock(DatabaseUserResource::class);
    $siteResource = Mockery::mock(SiteResource::class);

    $installingSite = new SiteData(
        id: 100,
        serverId: 12345,
        name: 'feat-timeout.review.example.com',
        aliases: null,
        directory: '/public',
        wildcards: false,
        status: 'installing',
        repository: 'user/repo',
        repositoryProvider: 'gitlab',
        repositoryBranch: 'main',
        repositoryStatus: 'installed',
        quickDeploy: true,
        deploymentStatus: 'deploying',
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

    $databaseResource->shouldReceive('create')->once()
        ->andReturn(new DatabaseData(id: 1, serverId: 12345, name: 'review_feat_timeout', status: 'installed', createdAt: now()->toDateTimeString()));
    $databaseUserResource->shouldReceive('create')->once()
        ->andReturn(new DatabaseUserData(id: 2, serverId: 12345, name: 'review_feat_timeout', status: 'installed', createdAt: now()->toDateTimeString(), databases: [1]));
    $siteResource->shouldReceive('create')->once()->andReturn($installingSite);
    $siteResource->shouldReceive('installGitRepository')->once()->andReturn($installingSite);
    $siteResource->shouldReceive('waitForRepositoryInstallation')->once()->andReturn($installingSite);
    $siteResource->shouldReceive('get')->andReturn($installingSite);
    $siteResource->shouldReceive('getEnvironment')->once()->andReturn('APP_NAME=Laravel');
    $siteResource->shouldReceive('updateEnvironment')->once();
    $siteResource->shouldReceive('updateDeploymentScript')->once();
    $siteResource->shouldReceive('enableQuickDeploy')->once();
    $siteResource->shouldReceive('deploy')->once();

    $forgeClient = Mockery::mock(ForgeClient::class);
    $forgeClient->shouldReceive('databases')->andReturn($databaseResource);
    $forgeClient->shouldReceive('databaseUsers')->andReturn($databaseUserResource);
    $forgeClient->shouldReceive('sites')->andReturn($siteResource);

    $builder = makeEnvironmentBuilder($forgeClient);
    $environment = $builder->create('feat/timeout');

    expect($environment)->toBeInstanceOf(EnvironmentData::class)
        ->domain->toBe('feat-timeout.review.example.com');
});
