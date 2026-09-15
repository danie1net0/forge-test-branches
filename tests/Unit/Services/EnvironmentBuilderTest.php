<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use Ddr\ForgeTestBranches\Data\{CertificateData, CreateDatabaseData, CreateDatabaseUserData, CreateSiteData, DatabaseData, DatabaseUserData, DomainData, EnvironmentData, SiteData};
use Ddr\ForgeTestBranches\Exceptions\ConfigurationException;
use Ddr\ForgeTestBranches\Integrations\Forge\{ForgeClient, ForgeConnector};
use Ddr\ForgeTestBranches\Integrations\Forge\Requests\Databases\{DeleteDatabaseRequest, DeleteDatabaseUserRequest};
use Ddr\ForgeTestBranches\Integrations\Forge\Requests\Sites\DeleteSiteRequest;
use Ddr\ForgeTestBranches\Integrations\Forge\Resources\{DatabaseResource, DatabaseUserResource, DomainResource, SiteResource};
use Ddr\ForgeTestBranches\Logger;
use Ddr\ForgeTestBranches\Services\{BranchSanitizer, DeploymentScriptBuilder, DomainBuilder, EnvironmentBuilder};
use Mockery\MockInterface;
use Saloon\Http\Faking\{MockClient, MockResponse};

beforeEach(function (): void {
    config([
        'forge-test-branches.server_id' => 12345,
        'forge-test-branches.domain.base' => 'review.example.com',
        'forge-test-branches.domain.pattern' => '{branch}.{base}',
        'forge-test-branches.database.prefix' => 'review_',
        'forge-test-branches.site.project_type' => 'laravel',
        'forge-test-branches.site.directory' => '/public',
        'forge-test-branches.site.isolated' => false,
        'forge-test-branches.site.php_version' => 'php84',
        'forge-test-branches.site.zero_downtime_deployments' => false,
        'forge-test-branches.git.provider' => 'gitlab',
        'forge-test-branches.git.repository' => 'user/repo',
        'forge-test-branches.deploy.script' => null,
        'forge-test-branches.deploy.quick_deploy' => true,
        'forge-test-branches.ssl.enabled' => false,
        'forge-test-branches.ssl.verification_method' => 'http-01',
        'forge-test-branches.ssl.key_type' => 'ecdsa',
        'forge-test-branches.env_variables' => [],
    ]);
});

function makeSiteData(int $id, string $name, string $status = 'installed'): SiteData
{
    return new SiteData(
        id: $id,
        serverId: 12345,
        name: $name,
        status: $status,
        repositoryProvider: 'gitlab',
        repositoryUrl: 'user/repo',
        repositoryBranch: 'main',
        repositoryStatus: 'installed',
    );
}

function makeDatabaseData(int $id, string $name, string $status = 'installed'): DatabaseData
{
    return new DatabaseData(id: $id, serverId: 12345, name: $name, status: $status, createdAt: '2025-07-29T09:00:00Z');
}

function makeDatabaseUserData(int $id, string $name, string $status = 'installed'): DatabaseUserData
{
    return new DatabaseUserData(id: $id, serverId: 12345, name: $name, status: $status, createdAt: '2025-07-29T09:00:00Z');
}

/**
 * @return array{forge: ForgeClient&MockInterface, sites: SiteResource&MockInterface, domains: DomainResource&MockInterface, databases: DatabaseResource&MockInterface, databaseUsers: DatabaseUserResource&MockInterface}
 */
function makeForgeMocks(): array
{
    $resources = [
        'sites' => Mockery::mock(SiteResource::class),
        'domains' => Mockery::mock(DomainResource::class),
        'databases' => Mockery::mock(DatabaseResource::class),
        'databaseUsers' => Mockery::mock(DatabaseUserResource::class),
    ];

    $forge = Mockery::mock(ForgeClient::class);

    foreach ($resources as $method => $resource) {
        $forge->shouldReceive($method)->andReturn($resource);
    }

    return [...$resources, 'forge' => $forge];
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

/**
 * @param array{forge: ForgeClient&MockInterface, sites: SiteResource&MockInterface, domains: DomainResource&MockInterface, databases: DatabaseResource&MockInterface, databaseUsers: DatabaseUserResource&MockInterface} $mocks
 */
function expectEnvironmentCreation(array $mocks, string $databaseName, string $domain): stdClass
{
    $recorder = new stdClass();
    $recorder->calls = [];
    $recorder->database = null;
    $recorder->databaseUser = null;
    $recorder->site = null;
    $recorder->environment = null;
    $recorder->script = null;

    $record = (fn (string $call, mixed $result): Closure => function () use ($recorder, $call, $result): mixed {
        $recorder->calls[] = $call;

        return $result;
    });

    $mocks['databases']->shouldReceive('create')
        ->once()
        ->withArgs(function (int $serverId, CreateDatabaseData $data) use ($recorder): bool {
            $recorder->database = $data;

            return $serverId === 12345;
        })
        ->andReturnUsing($record('databases.create', makeDatabaseData(1, $databaseName, 'installing')));

    $mocks['databases']->shouldReceive('waitForInstallation')
        ->once()
        ->with(12345, 1)
        ->andReturnUsing($record('databases.wait', makeDatabaseData(1, $databaseName)));

    $mocks['databaseUsers']->shouldReceive('create')
        ->once()
        ->withArgs(function (int $serverId, CreateDatabaseUserData $data) use ($recorder): bool {
            $recorder->databaseUser = $data;

            return $serverId === 12345;
        })
        ->andReturnUsing($record('databaseUsers.create', makeDatabaseUserData(2, $databaseName, 'installing')));

    $mocks['databaseUsers']->shouldReceive('waitForInstallation')
        ->once()
        ->with(12345, 2)
        ->andReturnUsing($record('databaseUsers.wait', makeDatabaseUserData(2, $databaseName)));

    $mocks['sites']->shouldReceive('create')
        ->once()
        ->withArgs(function (int $serverId, CreateSiteData $data) use ($recorder): bool {
            $recorder->site = $data;

            return $serverId === 12345;
        })
        ->andReturnUsing($record('sites.create', makeSiteData(100, $domain, 'creating')));

    $mocks['sites']->shouldReceive('waitForInstallation')
        ->once()
        ->with(12345, 100)
        ->andReturnUsing($record('sites.wait', makeSiteData(100, $domain)));

    $mocks['sites']->shouldReceive('getEnvironmentFile')
        ->once()
        ->with(12345, 100)
        ->andReturnUsing($record('sites.getEnvironmentFile', "APP_NAME=Laravel\nAPP_ENV=local"));

    $mocks['sites']->shouldReceive('updateEnvironmentFile')
        ->once()
        ->withArgs(function (int $serverId, int $siteId, string $content) use ($recorder): bool {
            $recorder->environment = $content;

            return $siteId === 100;
        })
        ->andReturnUsing($record('sites.updateEnvironmentFile', null));

    $mocks['sites']->shouldReceive('waitForEnvironmentFileContaining')
        ->once()
        ->with(12345, 100, "DB_DATABASE={$databaseName}")
        ->andReturnUsing($record('sites.waitForEnvironmentFileContaining', "DB_DATABASE={$databaseName}"));

    $mocks['sites']->shouldReceive('updateDeploymentScript')
        ->once()
        ->withArgs(function (int $serverId, int $siteId, string $content) use ($recorder): bool {
            $recorder->script = $content;

            return $siteId === 100;
        })
        ->andReturnUsing($record('sites.updateDeploymentScript', null));

    $mocks['sites']->shouldReceive('deploy')
        ->once()
        ->with(12345, 100)
        ->andReturnUsing($record('sites.deploy', null));

    if (config('forge-test-branches.deploy.quick_deploy') !== true) {
        $mocks['sites']->shouldNotReceive('enableQuickDeploy');

        return $recorder;
    }

    $mocks['sites']->shouldReceive('enableQuickDeploy')
        ->once()
        ->with(12345, 100)
        ->andReturnUsing($record('sites.enableQuickDeploy', null));

    return $recorder;
}

test('cria ambiente completo com sucesso', function (): void {
    $mocks = makeForgeMocks();
    $recorder = expectEnvironmentCreation($mocks, 'review_feat_hu_123', 'feat-hu-123.review.example.com');

    $environment = makeEnvironmentBuilder($mocks['forge'])->create('feat/hu-123');

    expect($environment)->toBeInstanceOf(EnvironmentData::class)
        ->branch->toBe('feat/hu-123')
        ->slug->toBe('feat-hu-123')
        ->domain->toBe('feat-hu-123.review.example.com')
        ->serverId->toBe(12345)
        ->siteId->toBe(100)
        ->databaseId->toBe(1)
        ->databaseUserId->toBe(2)
        ->and($recorder->calls)->toBe([
            'databases.create',
            'databases.wait',
            'databaseUsers.create',
            'databaseUsers.wait',
            'sites.create',
            'sites.wait',
            'sites.getEnvironmentFile',
            'sites.updateEnvironmentFile',
            'sites.waitForEnvironmentFileContaining',
            'sites.updateDeploymentScript',
            'sites.enableQuickDeploy',
            'sites.deploy',
        ]);
});

test('cria site com repositório, branch, dependências do composer e zero-downtime desligado', function (): void {
    config(['forge-test-branches.site.isolated' => true]);

    $mocks = makeForgeMocks();
    $recorder = expectEnvironmentCreation($mocks, 'review_feat_repo', 'feat-repo.review.example.com');

    makeEnvironmentBuilder($mocks['forge'])->create('feat/repo');

    expect($recorder->site->toArray())->toBe([
        'name' => 'feat-repo.review.example.com',
        'type' => 'laravel',
        'domain_mode' => 'custom',
        'www_redirect_type' => 'none',
        'allow_wildcard_subdomains' => false,
        'web_directory' => '/public',
        'is_isolated' => true,
        'php_version' => 'php84',
        'source_control_provider' => 'gitlab',
        'repository' => 'user/repo',
        'branch' => 'feat/repo',
        'install_composer_dependencies' => true,
        'zero_downtime_deployments' => false,
    ]);
});

test('encontra ambiente existente via Forge API', function (): void {
    $mocks = makeForgeMocks();

    $mocks['sites']->shouldReceive('findByName')
        ->once()
        ->with(12345, 'feat-hu-456.review.example.com')
        ->andReturn(makeSiteData(200, 'feat-hu-456.review.example.com'));

    $mocks['databases']->shouldReceive('findByName')
        ->once()
        ->with(12345, 'review_feat_hu_456')
        ->andReturn(makeDatabaseData(10, 'review_feat_hu_456'));

    $mocks['databaseUsers']->shouldReceive('findByName')
        ->once()
        ->with(12345, 'review_feat_hu_456')
        ->andReturn(makeDatabaseUserData(20, 'review_feat_hu_456'));

    $environment = makeEnvironmentBuilder($mocks['forge'])->find('feat/hu-456');

    expect($environment)->not->toBeNull()
        ->branch->toBe('feat/hu-456')
        ->siteId->toBe(200)
        ->databaseId->toBe(10)
        ->databaseUserId->toBe(20);
});

test('retorna null quando site não existe', function (): void {
    $mocks = makeForgeMocks();

    $mocks['sites']->shouldReceive('findByName')
        ->once()
        ->with(12345, 'feat-nonexistent.review.example.com')
        ->andReturnNull();

    expect(makeEnvironmentBuilder($mocks['forge'])->find('feat/nonexistent'))->toBeNull();
});

test('retorna null quando o site encontrado está sendo removido', function (string $status): void {
    $mocks = makeForgeMocks();

    $mocks['sites']->shouldReceive('findByName')
        ->once()
        ->with(12345, 'feat-removing.review.example.com')
        ->andReturn(makeSiteData(200, 'feat-removing.review.example.com', $status));

    expect(makeEnvironmentBuilder($mocks['forge'])->find('feat/removing'))->toBeNull();
})->with([
    'sendo removido' => ['removing'],
    'sendo desinstalado' => ['uninstalling'],
]);

test('verifica que ambiente existe', function (): void {
    $mocks = makeForgeMocks();

    $mocks['sites']->shouldReceive('findByName')
        ->once()
        ->with(12345, 'feat-exists.review.example.com')
        ->andReturn(makeSiteData(100, 'feat-exists.review.example.com'));
    $mocks['databases']->shouldReceive('findByName')->andReturnNull();
    $mocks['databaseUsers']->shouldReceive('findByName')->andReturnNull();

    expect(makeEnvironmentBuilder($mocks['forge'])->exists('feat/exists'))->toBeTrue();
});

test('verifica que ambiente não existe', function (): void {
    $mocks = makeForgeMocks();

    $mocks['sites']->shouldReceive('findByName')->once()->andReturnNull();

    expect(makeEnvironmentBuilder($mocks['forge'])->exists('feat/not-exists'))->toBeFalse();
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

    $mocks = makeForgeMocks();

    $mocks['sites']->shouldReceive('delete')->once()->with(12345, 200)->globally()->ordered();
    $mocks['databaseUsers']->shouldReceive('delete')->once()->with(12345, 20)->globally()->ordered();
    $mocks['databases']->shouldReceive('delete')->once()->with(12345, 10)->globally()->ordered();

    makeEnvironmentBuilder($mocks['forge'])->destroy($environment);
});

test('destrói ambiente sem database quando não existe', function (): void {
    $environment = new EnvironmentData(
        branch: 'feat/no-db',
        slug: 'feat-no-db',
        domain: 'feat-no-db.review.example.com',
        serverId: 12345,
        siteId: 300,
    );

    $mocks = makeForgeMocks();

    $mocks['sites']->shouldReceive('delete')->once()->with(12345, 300);
    $mocks['databaseUsers']->shouldNotReceive('delete');
    $mocks['databases']->shouldNotReceive('delete');

    makeEnvironmentBuilder($mocks['forge'])->destroy($environment);
});

test('destrói tratando recurso já removido (404) como sucesso', function (): void {
    $environment = new EnvironmentData(
        branch: 'feat/gone',
        slug: 'feat-gone',
        domain: 'feat-gone.review.example.com',
        serverId: 12345,
        siteId: 200,
        databaseId: 10,
        databaseUserId: 20,
    );

    $mockClient = new MockClient([
        DeleteSiteRequest::class => MockResponse::make(['message' => 'Not Found'], 404),
        DeleteDatabaseUserRequest::class => MockResponse::make(['message' => 'Not Found'], 404),
        DeleteDatabaseRequest::class => MockResponse::make(['message' => 'Not Found'], 404),
    ]);

    $connector = new ForgeConnector('test-token', 'test-org');
    $connector->withMockClient($mockClient);

    $forge = new ForgeClient(connector: $connector);

    makeEnvironmentBuilder($forge)->destroy($environment);

    $mockClient->assertSentCount(3);
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

    $mocks = makeForgeMocks();

    $mocks['sites']->shouldReceive('deploy')->once()->with(12345, 300);

    makeEnvironmentBuilder($mocks['forge'])->deploy($environment);
});

test('envia dados corretos para criação de usuário de database', function (): void {
    $mocks = makeForgeMocks();
    $recorder = expectEnvironmentCreation($mocks, 'review_feat_test', 'feat-test.review.example.com');

    makeEnvironmentBuilder($mocks['forge'])->create('feat/test');

    $capturedData = $recorder->databaseUser->toArray();

    expect($capturedData)
        ->toHaveKeys(['name', 'password', 'database_ids'])
        ->and($capturedData['name'])->toBe('review_feat_test')
        ->and($capturedData['password'])->toMatch('/^[a-zA-Z0-9]+$/')
        ->and(mb_strlen((string) $capturedData['password']))->toBe(32)
        ->and($capturedData['database_ids'])->toBe([1])
        ->and($recorder->environment)->toContain('DB_PASSWORD=' . $capturedData['password']);
});

test('trunca nome do database respeitando limite de 32 caracteres', function (): void {
    $mocks = makeForgeMocks();
    $recorder = expectEnvironmentCreation($mocks, 'review_sprint_16_feature_t_abc123', 'sprint-16-feature-test-branches.review.example.com');

    makeEnvironmentBuilder($mocks['forge'])->create('sprint-16/feature/test-branches');

    $databaseName = $recorder->database->name;

    expect(mb_strlen($databaseName))->toBeLessThanOrEqual(32)
        ->and($databaseName)->toStartWith('review_')
        ->toMatch('/^review_sprint_16_feature_[a-z0-9_]+$/')
        ->and($recorder->databaseUser->name)->toBe($databaseName);
});

test('processa placeholders {slug} e {env:VAR} nas variáveis de ambiente', function (): void {
    putenv('BASE_APP_KEY=key123');

    config([
        'forge-test-branches.env_variables' => [
            'APP_URL' => 'https://{slug}.review.example.com',
            'APP_KEY' => '{env:BASE_APP_KEY}',
        ],
    ]);

    $mocks = makeForgeMocks();
    $recorder = expectEnvironmentCreation($mocks, 'review_test', 'test.review.example.com');

    makeEnvironmentBuilder($mocks['forge'])->create('test');

    expect($recorder->environment)
        ->toContain('APP_NAME=Laravel')
        ->toContain('APP_URL=https://test.review.example.com')
        ->toContain('APP_KEY=key123')
        ->toContain('DB_DATABASE=review_test')
        ->toContain('DB_USERNAME=review_test');

    putenv('BASE_APP_KEY');
});

test('grava valores do .env em formato que o phpdotenv aceita sem interpolar $', function (): void {
    config([
        'forge-test-branches.env_variables' => [
            'MAIL_FROM_NAME' => 'ESC Solutions',
            'QUOTED_TEXT' => 'He said "hi" \\o/',
            'HASH_VALUE' => 'abc#123',
            'PASSWORD_WITH_DOLLAR' => 'pa$$word',
            'NAME_WITH_SINGLE_QUOTE' => "O'Brien",
            'QUOTE_AND_DOLLAR' => "O'Brien \$5 off",
            'PLAIN_VALUE' => 'https://feat.review.example.com',
        ],
    ]);

    $mocks = makeForgeMocks();
    $recorder = expectEnvironmentCreation($mocks, 'review_feat_quotes', 'feat-quotes.review.example.com');

    makeEnvironmentBuilder($mocks['forge'])->create('feat/quotes');

    // Dotenv::parse() runs the full pipeline, including `$` interpolation,
    // unlike the raw Parser: it is the only way to prove a dollar sign
    // survives as a literal character instead of being expanded.
    $parsedValues = Dotenv::parse($recorder->environment);

    expect($parsedValues)
        ->toHaveKey('MAIL_FROM_NAME', 'ESC Solutions')
        ->toHaveKey('QUOTED_TEXT', 'He said "hi" \\o/')
        ->toHaveKey('HASH_VALUE', 'abc#123')
        ->toHaveKey('PASSWORD_WITH_DOLLAR', 'pa$$word')
        ->toHaveKey('NAME_WITH_SINGLE_QUOTE', "O'Brien")
        ->toHaveKey('QUOTE_AND_DOLLAR', "O'Brien \$5 off")
        ->and($recorder->environment)
        ->toContain('PLAIN_VALUE=https://feat.review.example.com')
        ->toContain('DB_DATABASE=review_feat_quotes');
});

test('substitui quebras de linha por espaço para não corromper o merge na próxima atualização', function (): void {
    config([
        'forge-test-branches.env_variables' => [
            'MULTI_LINE' => "line one\nline two\r\nline three",
        ],
    ]);

    $mocks = makeForgeMocks();
    $recorder = expectEnvironmentCreation($mocks, 'review_feat_newline', 'feat-newline.review.example.com');

    makeEnvironmentBuilder($mocks['forge'])->create('feat/newline');

    expect($recorder->environment)
        ->not->toContain("line one\n")
        ->and(Dotenv::parse($recorder->environment))
        ->toHaveKey('MULTI_LINE', 'line one line two line three');
});

test('lista todos os ambientes de review do servidor', function (): void {
    $mocks = makeForgeMocks();

    $mocks['sites']->shouldReceive('list')
        ->once()
        ->with(12345)
        ->andReturn([
            makeSiteData(100, 'feat-login.review.example.com'),
            makeSiteData(200, 'fix-bug.review.example.com'),
            makeSiteData(300, 'production.other-domain.com'),
        ]);

    $environments = makeEnvironmentBuilder($mocks['forge'])->listAll();

    expect($environments)->toHaveCount(2)
        ->and($environments[0])->toBeInstanceOf(EnvironmentData::class)
        ->siteId->toBe(100)
        ->domain->toBe('feat-login.review.example.com')
        ->and($environments[1])->toBeInstanceOf(EnvironmentData::class)
        ->siteId->toBe(200)
        ->domain->toBe('fix-bug.review.example.com');
});

test('retorna array vazio quando não há ambientes de review', function (): void {
    $mocks = makeForgeMocks();

    $mocks['sites']->shouldReceive('list')
        ->once()
        ->with(12345)
        ->andReturn([makeSiteData(100, 'production.other-domain.com')]);

    expect(makeEnvironmentBuilder($mocks['forge'])->listAll())->toBeEmpty();
});

test('usa repositoryBranch do site como branch do ambiente', function (): void {
    $mocks = makeForgeMocks();

    $mocks['sites']->shouldReceive('list')
        ->once()
        ->andReturn([makeSiteData(100, 'feat-login.review.example.com')]);

    $environments = makeEnvironmentBuilder($mocks['forge'])->listAll();

    expect($environments[0]->branch)->toBe('main');
});

test('cria ambiente com certificado SSL no domínio do site quando habilitado', function (): void {
    config(['forge-test-branches.ssl.enabled' => true]);

    $mocks = makeForgeMocks();
    expectEnvironmentCreation($mocks, 'review_feat_ssl', 'feat-ssl.review.example.com');

    $domainRecord = new DomainData(id: 10, serverId: 12345, siteId: 100, name: 'feat-ssl.review.example.com', type: 'primary', status: 'enabled');

    $mocks['domains']->shouldReceive('findByName')
        ->once()
        ->with(12345, 100, 'feat-ssl.review.example.com')
        ->andReturn($domainRecord);
    $mocks['domains']->shouldReceive('waitForEnabled')
        ->once()
        ->with(12345, 100, 10)
        ->andReturn($domainRecord);
    $mocks['domains']->shouldReceive('obtainLetsEncryptCertificate')
        ->once()
        ->with(12345, 100, 10, 'http-01', 'ecdsa')
        ->andReturn(new CertificateData(id: 5, serverId: 12345, siteId: 100, domainId: 10, type: 'letsencrypt', requestStatus: 'creating', status: 'installing', active: false));
    $mocks['domains']->shouldReceive('waitForCertificateActivation')
        ->once()
        ->with(12345, 100, 10, 5)
        ->andReturn(new CertificateData(id: 5, serverId: 12345, siteId: 100, domainId: 10, type: 'letsencrypt', requestStatus: 'created', status: 'installed', active: true));

    $environment = makeEnvironmentBuilder($mocks['forge'])->create('feat/ssl');

    expect($environment)->toBeInstanceOf(EnvironmentData::class)
        ->domain->toBe('feat-ssl.review.example.com');
});

test('faz rollback quando o domínio não é encontrado para emitir SSL', function (): void {
    config(['forge-test-branches.ssl.enabled' => true]);

    $mocks = makeForgeMocks();

    $mocks['databases']->shouldReceive('create')->once()->andReturn(makeDatabaseData(1, 'review_feat_ssl'));
    $mocks['databases']->shouldReceive('waitForInstallation')->once()->andReturn(makeDatabaseData(1, 'review_feat_ssl'));
    $mocks['databaseUsers']->shouldReceive('create')->once()->andReturn(makeDatabaseUserData(2, 'review_feat_ssl'));
    $mocks['databaseUsers']->shouldReceive('waitForInstallation')->once()->andReturn(makeDatabaseUserData(2, 'review_feat_ssl'));
    $mocks['sites']->shouldReceive('create')->once()->andReturn(makeSiteData(100, 'feat-ssl.review.example.com'));
    $mocks['sites']->shouldReceive('waitForInstallation')->once()->andReturn(makeSiteData(100, 'feat-ssl.review.example.com'));
    $mocks['sites']->shouldReceive('getEnvironmentFile')->once()->andReturn('APP_NAME=Laravel');
    $mocks['sites']->shouldReceive('updateEnvironmentFile')->once();
    $mocks['sites']->shouldReceive('waitForEnvironmentFileContaining')->once();
    $mocks['sites']->shouldReceive('updateDeploymentScript')->once();
    $mocks['domains']->shouldReceive('findByName')->once()->andReturnNull();
    $mocks['domains']->shouldNotReceive('waitForEnabled');
    $mocks['domains']->shouldNotReceive('obtainLetsEncryptCertificate');
    $mocks['sites']->shouldNotReceive('deploy');

    $mocks['sites']->shouldReceive('delete')->once()->with(12345, 100);
    $mocks['databaseUsers']->shouldReceive('delete')->once()->with(12345, 2);
    $mocks['databases']->shouldReceive('delete')->once()->with(12345, 1);

    expect(fn (): EnvironmentData => makeEnvironmentBuilder($mocks['forge'])->create('feat/ssl'))
        ->toThrow(RuntimeException::class, 'Domain not found on site: feat-ssl.review.example.com');
});

test('não habilita quick deploy quando desabilitado', function (): void {
    config(['forge-test-branches.deploy.quick_deploy' => false]);

    $mocks = makeForgeMocks();
    $recorder = expectEnvironmentCreation($mocks, 'review_feat_no_qd', 'feat-no-qd.review.example.com');

    $environment = makeEnvironmentBuilder($mocks['forge'])->create('feat/no-qd');

    expect($environment)->toBeInstanceOf(EnvironmentData::class)
        ->domain->toBe('feat-no-qd.review.example.com')
        ->and($recorder->calls)->not->toContain('sites.enableQuickDeploy');
});

test('lança exceção de configuração quando o server_id não está definido, sem criar recursos', function (mixed $serverId): void {
    config(['forge-test-branches.server_id' => $serverId]);

    $mocks = makeForgeMocks();
    $mocks['databases']->shouldNotReceive('create');
    $mocks['databaseUsers']->shouldNotReceive('create');
    $mocks['sites']->shouldNotReceive('create');

    expect(fn (): EnvironmentData => makeEnvironmentBuilder($mocks['forge'])->create('feat/invalid'))
        ->toThrow(ConfigurationException::class, 'Forge server ID not configured');
})->with([
    'ausente' => [null],
    'zero' => [0],
    'negativo' => [-1],
    'não numérico' => ['abc'],
]);

test('lança exceção de configuração para tipo de site inválido, sem criar recursos', function (): void {
    config(['forge-test-branches.site.project_type' => 'html']);

    $mocks = makeForgeMocks();
    $mocks['databases']->shouldNotReceive('create');
    $mocks['databaseUsers']->shouldNotReceive('create');
    $mocks['sites']->shouldNotReceive('create');

    expect(fn (): EnvironmentData => makeEnvironmentBuilder($mocks['forge'])->create('feat/invalid'))
        ->toThrow(ConfigurationException::class, 'Invalid site type "html"');
});

test('lança exceção de configuração para provedor git inválido, sem criar recursos', function (): void {
    config(['forge-test-branches.git.provider' => 'svn']);

    $mocks = makeForgeMocks();
    $mocks['databases']->shouldNotReceive('create');
    $mocks['databaseUsers']->shouldNotReceive('create');
    $mocks['sites']->shouldNotReceive('create');

    expect(fn (): EnvironmentData => makeEnvironmentBuilder($mocks['forge'])->create('feat/invalid'))
        ->toThrow(ConfigurationException::class, 'Invalid git provider "svn"');
});

test('lança exceção de configuração quando o repositório não está definido, sem criar recursos', function (): void {
    config(['forge-test-branches.git.repository' => null]);

    $mocks = makeForgeMocks();
    $mocks['databases']->shouldNotReceive('create');
    $mocks['databaseUsers']->shouldNotReceive('create');
    $mocks['sites']->shouldNotReceive('create');

    expect(fn (): EnvironmentData => makeEnvironmentBuilder($mocks['forge'])->create('feat/invalid'))
        ->toThrow(ConfigurationException::class, 'Git repository not configured');
});

test('faz rollback ao falhar na instalação do site', function (): void {
    $mocks = makeForgeMocks();

    $mocks['databases']->shouldReceive('create')->once()->andReturn(makeDatabaseData(1, 'review_feat_fail', 'installing'));
    $mocks['databases']->shouldReceive('waitForInstallation')->once()->andReturn(makeDatabaseData(1, 'review_feat_fail'));
    $mocks['databaseUsers']->shouldReceive('create')->once()->andReturn(makeDatabaseUserData(2, 'review_feat_fail', 'installing'));
    $mocks['databaseUsers']->shouldReceive('waitForInstallation')->once()->andReturn(makeDatabaseUserData(2, 'review_feat_fail'));
    $mocks['sites']->shouldReceive('create')->once()->andReturn(makeSiteData(100, 'feat-fail.review.example.com', 'creating'));
    $mocks['sites']->shouldReceive('waitForInstallation')->once()
        ->andThrow(new RuntimeException('Timeout waiting for site installation'));

    $mocks['sites']->shouldReceive('delete')->once()->with(12345, 100);
    $mocks['databaseUsers']->shouldReceive('delete')->once()->with(12345, 2);
    $mocks['databases']->shouldReceive('delete')->once()->with(12345, 1);

    expect(fn (): EnvironmentData => makeEnvironmentBuilder($mocks['forge'])->create('feat/fail'))
        ->toThrow(RuntimeException::class, 'Timeout waiting for site installation');
});

test('faz rollback da database quando a instalação da database expira', function (): void {
    $mocks = makeForgeMocks();

    $mocks['databases']->shouldReceive('create')->once()->andReturn(makeDatabaseData(1, 'review_feat_fail', 'installing'));
    $mocks['databases']->shouldReceive('waitForInstallation')->once()
        ->andThrow(new RuntimeException('Timeout waiting for database installation'));

    $mocks['databaseUsers']->shouldNotReceive('create');
    $mocks['sites']->shouldNotReceive('create');
    $mocks['sites']->shouldNotReceive('delete');
    $mocks['databaseUsers']->shouldNotReceive('delete');
    $mocks['databases']->shouldReceive('delete')->once()->with(12345, 1);

    expect(fn (): EnvironmentData => makeEnvironmentBuilder($mocks['forge'])->create('feat/fail'))
        ->toThrow(RuntimeException::class, 'Timeout waiting for database installation');
});

test('faz rollback apenas da database quando a criação do usuário falha', function (): void {
    $mocks = makeForgeMocks();

    $mocks['databases']->shouldReceive('create')->once()->andReturn(makeDatabaseData(1, 'review_feat_userfail'));
    $mocks['databases']->shouldReceive('waitForInstallation')->once()->andReturn(makeDatabaseData(1, 'review_feat_userfail'));
    $mocks['databaseUsers']->shouldReceive('create')->once()->andThrow(new RuntimeException('User creation failed'));

    $mocks['sites']->shouldNotReceive('create');
    $mocks['sites']->shouldNotReceive('delete');
    $mocks['databaseUsers']->shouldNotReceive('delete');
    $mocks['databases']->shouldReceive('delete')->once()->with(12345, 1);

    expect(fn (): EnvironmentData => makeEnvironmentBuilder($mocks['forge'])->create('feat/userfail'))
        ->toThrow(RuntimeException::class, 'User creation failed');
});

test('faz rollback do usuário e da database quando a espera do usuário expira', function (): void {
    $mocks = makeForgeMocks();

    $mocks['databases']->shouldReceive('create')->once()->andReturn(makeDatabaseData(1, 'review_feat_userwait'));
    $mocks['databases']->shouldReceive('waitForInstallation')->once()->andReturn(makeDatabaseData(1, 'review_feat_userwait'));
    $mocks['databaseUsers']->shouldReceive('create')->once()->andReturn(makeDatabaseUserData(2, 'review_feat_userwait', 'installing'));
    $mocks['databaseUsers']->shouldReceive('waitForInstallation')->once()
        ->andThrow(new RuntimeException('Timeout waiting for database user installation'));

    $mocks['sites']->shouldNotReceive('create');
    $mocks['sites']->shouldNotReceive('delete');
    $mocks['databaseUsers']->shouldReceive('delete')->once()->with(12345, 2);
    $mocks['databases']->shouldReceive('delete')->once()->with(12345, 1);

    expect(fn (): EnvironmentData => makeEnvironmentBuilder($mocks['forge'])->create('feat/userwait'))
        ->toThrow(RuntimeException::class, 'Timeout waiting for database user installation');
});

test('faz rollback completo quando a criação do site falha', function (): void {
    $mocks = makeForgeMocks();

    $mocks['databases']->shouldReceive('create')->once()->andReturn(makeDatabaseData(1, 'review_feat_sitefail'));
    $mocks['databases']->shouldReceive('waitForInstallation')->once()->andReturn(makeDatabaseData(1, 'review_feat_sitefail'));
    $mocks['databaseUsers']->shouldReceive('create')->once()->andReturn(makeDatabaseUserData(2, 'review_feat_sitefail'));
    $mocks['databaseUsers']->shouldReceive('waitForInstallation')->once()->andReturn(makeDatabaseUserData(2, 'review_feat_sitefail'));
    $mocks['sites']->shouldReceive('create')->once()->andThrow(new RuntimeException('Unprocessable Entity (422)'));

    $mocks['sites']->shouldNotReceive('delete');
    $mocks['databaseUsers']->shouldReceive('delete')->once()->with(12345, 2);
    $mocks['databases']->shouldReceive('delete')->once()->with(12345, 1);

    expect(fn (): EnvironmentData => makeEnvironmentBuilder($mocks['forge'])->create('feat/sitefail'))
        ->toThrow(RuntimeException::class, 'Unprocessable Entity (422)');
});

test('faz rollback completo quando a escrita do .env nunca é confirmada', function (): void {
    $mocks = makeForgeMocks();

    $mocks['databases']->shouldReceive('create')->once()->andReturn(makeDatabaseData(1, 'review_feat_envfail'));
    $mocks['databases']->shouldReceive('waitForInstallation')->once()->andReturn(makeDatabaseData(1, 'review_feat_envfail'));
    $mocks['databaseUsers']->shouldReceive('create')->once()->andReturn(makeDatabaseUserData(2, 'review_feat_envfail'));
    $mocks['databaseUsers']->shouldReceive('waitForInstallation')->once()->andReturn(makeDatabaseUserData(2, 'review_feat_envfail'));
    $mocks['sites']->shouldReceive('create')->once()->andReturn(makeSiteData(100, 'feat-envfail.review.example.com'));
    $mocks['sites']->shouldReceive('waitForInstallation')->once()->andReturn(makeSiteData(100, 'feat-envfail.review.example.com'));
    $mocks['sites']->shouldReceive('getEnvironmentFile')->once()->andReturn('APP_NAME=Laravel');
    $mocks['sites']->shouldReceive('updateEnvironmentFile')->once();
    $mocks['sites']->shouldReceive('waitForEnvironmentFileContaining')->once()
        ->andThrow(new RuntimeException('Timeout waiting for environment file update'));

    $mocks['sites']->shouldReceive('delete')->once()->with(12345, 100);
    $mocks['databaseUsers']->shouldReceive('delete')->once()->with(12345, 2);
    $mocks['databases']->shouldReceive('delete')->once()->with(12345, 1);

    expect(fn (): EnvironmentData => makeEnvironmentBuilder($mocks['forge'])->create('feat/envfail'))
        ->toThrow(RuntimeException::class, 'Timeout waiting for environment file update');
});

test('faz rollback completo quando o deploy falha', function (): void {
    $mocks = makeForgeMocks();

    $mocks['databases']->shouldReceive('create')->once()->andReturn(makeDatabaseData(1, 'review_feat_deployfail'));
    $mocks['databases']->shouldReceive('waitForInstallation')->once()->andReturn(makeDatabaseData(1, 'review_feat_deployfail'));
    $mocks['databaseUsers']->shouldReceive('create')->once()->andReturn(makeDatabaseUserData(2, 'review_feat_deployfail'));
    $mocks['databaseUsers']->shouldReceive('waitForInstallation')->once()->andReturn(makeDatabaseUserData(2, 'review_feat_deployfail'));
    $mocks['sites']->shouldReceive('create')->once()->andReturn(makeSiteData(100, 'feat-deployfail.review.example.com'));
    $mocks['sites']->shouldReceive('waitForInstallation')->once()->andReturn(makeSiteData(100, 'feat-deployfail.review.example.com'));
    $mocks['sites']->shouldReceive('getEnvironmentFile')->once()->andReturn('APP_NAME=Laravel');
    $mocks['sites']->shouldReceive('updateEnvironmentFile')->once();
    $mocks['sites']->shouldReceive('waitForEnvironmentFileContaining')->once();
    $mocks['sites']->shouldReceive('updateDeploymentScript')->once();
    $mocks['sites']->shouldReceive('enableQuickDeploy')->once();
    $mocks['sites']->shouldReceive('deploy')->once()->andThrow(new RuntimeException('Deploy trigger failed'));

    $mocks['sites']->shouldReceive('delete')->once()->with(12345, 100);
    $mocks['databaseUsers']->shouldReceive('delete')->once()->with(12345, 2);
    $mocks['databases']->shouldReceive('delete')->once()->with(12345, 1);

    expect(fn (): EnvironmentData => makeEnvironmentBuilder($mocks['forge'])->create('feat/deployfail'))
        ->toThrow(RuntimeException::class, 'Deploy trigger failed');
});

test('rollback trata erros individuais sem propagar', function (): void {
    $mocks = makeForgeMocks();

    $mocks['databases']->shouldReceive('create')->once()->andReturn(makeDatabaseData(1, 'review_feat_rb'));
    $mocks['databases']->shouldReceive('waitForInstallation')->once()->andReturn(makeDatabaseData(1, 'review_feat_rb'));
    $mocks['databaseUsers']->shouldReceive('create')->once()->andReturn(makeDatabaseUserData(2, 'review_feat_rb'));
    $mocks['databaseUsers']->shouldReceive('waitForInstallation')->once()->andReturn(makeDatabaseUserData(2, 'review_feat_rb'));
    $mocks['sites']->shouldReceive('create')->once()->andReturn(makeSiteData(100, 'feat-rb.review.example.com'));
    $mocks['sites']->shouldReceive('waitForInstallation')->once()
        ->andThrow(new RuntimeException('Site installation failed'));

    $mocks['sites']->shouldReceive('delete')->once()->andThrow(new RuntimeException('Site delete failed'));
    $mocks['databaseUsers']->shouldReceive('delete')->once()->andThrow(new RuntimeException('User delete failed'));
    $mocks['databases']->shouldReceive('delete')->once()->andThrow(new RuntimeException('DB delete failed'));

    expect(fn (): EnvironmentData => makeEnvironmentBuilder($mocks['forge'])->create('feat/rb'))
        ->toThrow(RuntimeException::class, 'Site installation failed');
});

test('rollback ignora recursos null', function (): void {
    $mocks = makeForgeMocks();

    $mocks['databases']->shouldReceive('create')->once()
        ->andThrow(new RuntimeException('Database creation failed'));

    $mocks['sites']->shouldNotReceive('delete');
    $mocks['databaseUsers']->shouldNotReceive('delete');
    $mocks['databases']->shouldNotReceive('delete');

    expect(fn (): EnvironmentData => makeEnvironmentBuilder($mocks['forge'])->create('feat/fail-early'))
        ->toThrow(RuntimeException::class, 'Database creation failed');
});

test('lança exceção de destruição parcial quando falha ao deletar recurso', function (string $failingResource): void {
    $environment = new EnvironmentData(
        branch: 'feat/partial',
        slug: 'feat-partial',
        domain: 'feat-partial.review.example.com',
        serverId: 12345,
        siteId: 200,
        databaseId: 10,
        databaseUserId: 20,
    );

    $mocks = makeForgeMocks();

    foreach (['sites', 'databaseUsers', 'databases'] as $resource) {
        $expectation = $mocks[$resource]->shouldReceive('delete')->once();

        if ($resource === $failingResource) {
            $expectation->andThrow(new RuntimeException("{$resource} delete error"));
        }
    }

    expect(fn (): null => makeEnvironmentBuilder($mocks['forge'])->destroy($environment) ?? null)
        ->toThrow(RuntimeException::class, 'Partial destruction');
})->with(['sites', 'databaseUsers', 'databases']);

test('retorna array vazio quando env_variables não é array', function (): void {
    config(['forge-test-branches.env_variables' => 'not-an-array']);

    $mocks = makeForgeMocks();
    $recorder = expectEnvironmentCreation($mocks, 'review_feat_env', 'feat-env.review.example.com');

    makeEnvironmentBuilder($mocks['forge'])->create('feat/env');

    expect($recorder->environment)->not->toContain('not-an-array');
});

test('processa placeholder {env:VAR} com valor null retornando vazio', function (): void {
    putenv('NONEXISTENT_VAR');

    config([
        'forge-test-branches.env_variables' => [
            'CUSTOM_VAR' => '{env:NONEXISTENT_VAR}',
        ],
    ]);

    $mocks = makeForgeMocks();
    $recorder = expectEnvironmentCreation($mocks, 'review_feat_null', 'feat-null.review.example.com');

    makeEnvironmentBuilder($mocks['forge'])->create('feat/null');

    expect($recorder->environment)->toContain('CUSTOM_VAR=');
});

test('envia script de deploy com a branch do ambiente', function (): void {
    $mocks = makeForgeMocks();
    $recorder = expectEnvironmentCreation($mocks, 'review_feat_script', 'feat-script.review.example.com');

    makeEnvironmentBuilder($mocks['forge'])->create('feat/script');

    expect($recorder->script)
        ->toContain('git fetch origin feat/script')
        ->toContain('git reset --hard origin/feat/script');
});
