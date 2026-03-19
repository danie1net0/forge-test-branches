<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\{CreateSiteData, InstallGitRepositoryData, SiteData};
use Ddr\ForgeTestBranches\Integrations\Forge\ForgeConnector;
use Ddr\ForgeTestBranches\Integrations\Forge\Resources\SiteResource;
use Saloon\Http\Faking\{MockClient, MockResponse};
use Ddr\ForgeTestBranches\Integrations\Forge\Requests\Sites\{CreateSiteRequest, DeleteSiteRequest, DeploySiteRequest, EnableQuickDeployRequest, GetEnvironmentRequest, GetSiteRequest, InstallGitRepositoryRequest, ListSitesRequest, UpdateDeploymentScriptRequest, UpdateEnvironmentRequest};

/** @return array<string, mixed> */
function makeSitePayload(int $id = 100, string $name = 'test.example.com'): array
{
    return [
        'id' => $id,
        'name' => $name,
        'aliases' => null,
        'directory' => '/public',
        'wildcards' => false,
        'status' => 'installed',
        'repository' => 'user/repo',
        'repository_provider' => 'gitlab',
        'repository_branch' => 'main',
        'repository_status' => 'installed',
        'quick_deploy' => true,
        'deployment_status' => null,
        'project_type' => 'php',
        'app' => null,
        'app_status' => null,
        'hipchat_room' => null,
        'slack_channel' => null,
        'telegram_chat_id' => null,
        'telegram_chat_title' => null,
        'teams_webhook_url' => null,
        'discord_webhook_url' => null,
        'username' => 'forge',
        'balancing_status' => null,
        'created_at' => '2024-01-01 00:00:00',
        'deployment_url' => null,
        'is_secured' => false,
        'php_version' => 'php84',
        'tags' => null,
        'failure_deployment_emails' => null,
        'telegram_secret' => null,
        'web_directory' => '/public',
    ];
}

test('lista sites do servidor', function (): void {
    $mockClient = new MockClient([
        ListSitesRequest::class => MockResponse::make([
            'sites' => [
                makeSitePayload(1, 'site1.example.com'),
                makeSitePayload(2, 'site2.example.com'),
            ],
        ]),
    ]);

    $connector = new ForgeConnector('test-token');
    $connector->withMockClient($mockClient);

    $resource = new SiteResource($connector);
    $result = $resource->list(123);

    expect($result)->toHaveCount(2)
        ->and($result[0])->toBeInstanceOf(SiteData::class)
        ->id->toBe(1)
        ->name->toBe('site1.example.com')
        ->and($result[1])->toBeInstanceOf(SiteData::class)
        ->id->toBe(2);
});

test('obtém site por id', function (): void {
    $mockClient = new MockClient([
        GetSiteRequest::class => MockResponse::make([
            'site' => makeSitePayload(456, 'test.example.com'),
        ]),
    ]);

    $connector = new ForgeConnector('test-token');
    $connector->withMockClient($mockClient);

    $resource = new SiteResource($connector);
    $result = $resource->get(123, 456);

    expect($result)->toBeInstanceOf(SiteData::class)
        ->id->toBe(456)
        ->name->toBe('test.example.com');
});

test('encontra site pelo domínio', function (): void {
    $mockClient = new MockClient([
        ListSitesRequest::class => MockResponse::make([
            'sites' => [
                makeSitePayload(1, 'site1.example.com'),
                makeSitePayload(2, 'target.example.com'),
            ],
        ]),
    ]);

    $connector = new ForgeConnector('test-token');
    $connector->withMockClient($mockClient);

    $resource = new SiteResource($connector);
    $result = $resource->findByDomain(123, 'target.example.com');

    expect($result)->toBeInstanceOf(SiteData::class)
        ->id->toBe(2)
        ->name->toBe('target.example.com');
});

test('retorna null quando site não é encontrado pelo domínio', function (): void {
    $mockClient = new MockClient([
        ListSitesRequest::class => MockResponse::make([
            'sites' => [
                makeSitePayload(1, 'site1.example.com'),
            ],
        ]),
    ]);

    $connector = new ForgeConnector('test-token');
    $connector->withMockClient($mockClient);

    $resource = new SiteResource($connector);

    expect($resource->findByDomain(123, 'nonexistent.example.com'))->toBeNull();
});

test('aguarda instalação do repositório com sucesso', function (): void {
    $mockClient = new MockClient([
        MockResponse::make(['site' => array_merge(makeSitePayload(), ['repository_status' => 'installing'])]),
        MockResponse::make(['site' => array_merge(makeSitePayload(), ['repository_status' => 'installed'])]),
    ]);

    $connector = new ForgeConnector('test-token');
    $connector->withMockClient($mockClient);

    $resource = new SiteResource($connector);
    $result = $resource->waitForRepositoryInstallation(123, 100, 3, 0);

    expect($result)->toBeInstanceOf(SiteData::class)
        ->repositoryStatus->toBe('installed');
});

test('lança exceção quando instalação do repositório expira', function (): void {
    $mockClient = new MockClient([
        MockResponse::make(['site' => array_merge(makeSitePayload(), ['repository_status' => 'installing'])]),
    ]);

    $connector = new ForgeConnector('test-token');
    $connector->withMockClient($mockClient);

    $resource = new SiteResource($connector);
    $resource->waitForRepositoryInstallation(123, 100, 1, 0);
})->throws(RuntimeException::class, 'Timeout waiting for repository installation');

test('cria site e retorna SiteData', function (): void {
    $mockClient = new MockClient([
        CreateSiteRequest::class => MockResponse::make([
            'site' => makeSitePayload(200, 'new.example.com'),
        ]),
    ]);

    $connector = new ForgeConnector('test-token');
    $connector->withMockClient($mockClient);

    $resource = new SiteResource($connector);
    $result = $resource->create(123, new CreateSiteData(domain: 'new.example.com', projectType: 'php'));

    expect($result)->toBeInstanceOf(SiteData::class)
        ->id->toBe(200)
        ->name->toBe('new.example.com');
});

test('instala repositório git no site', function (): void {
    $mockClient = new MockClient([
        InstallGitRepositoryRequest::class => MockResponse::make([
            'site' => array_merge(makeSitePayload(), ['repository_status' => 'installing']),
        ]),
    ]);

    $connector = new ForgeConnector('test-token');
    $connector->withMockClient($mockClient);

    $resource = new SiteResource($connector);
    $result = $resource->installGitRepository(
        123,
        100,
        new InstallGitRepositoryData(provider: 'gitlab', repository: 'user/repo', branch: 'main')
    );

    expect($result)->toBeInstanceOf(SiteData::class)
        ->repositoryStatus->toBe('installing');
});

test('deleta site com sucesso', function (): void {
    $mockClient = new MockClient([
        DeleteSiteRequest::class => MockResponse::make([]),
    ]);

    $connector = new ForgeConnector('test-token');
    $connector->withMockClient($mockClient);

    $resource = new SiteResource($connector);
    $resource->delete(123, 456);

    $mockClient->assertSent(DeleteSiteRequest::class);
});

test('executa deploy do site com sucesso', function (): void {
    $mockClient = new MockClient([
        DeploySiteRequest::class => MockResponse::make([]),
    ]);

    $connector = new ForgeConnector('test-token');
    $connector->withMockClient($mockClient);

    $resource = new SiteResource($connector);
    $resource->deploy(123, 456);

    $mockClient->assertSent(DeploySiteRequest::class);
});

test('atualiza script de deploy', function (): void {
    $mockClient = new MockClient([
        UpdateDeploymentScriptRequest::class => MockResponse::make([]),
    ]);

    $connector = new ForgeConnector('test-token');
    $connector->withMockClient($mockClient);

    $resource = new SiteResource($connector);
    $resource->updateDeploymentScript(123, 456, 'cd /home/forge && git pull');

    $mockClient->assertSent(UpdateDeploymentScriptRequest::class);
});

test('habilita quick deploy', function (): void {
    $mockClient = new MockClient([
        EnableQuickDeployRequest::class => MockResponse::make([]),
    ]);

    $connector = new ForgeConnector('test-token');
    $connector->withMockClient($mockClient);

    $resource = new SiteResource($connector);
    $resource->enableQuickDeploy(123, 456);

    $mockClient->assertSent(EnableQuickDeployRequest::class);
});

test('obtém variáveis de ambiente do site', function (): void {
    $mockClient = new MockClient([
        GetEnvironmentRequest::class => MockResponse::make('APP_ENV=production'),
    ]);

    $connector = new ForgeConnector('test-token');
    $connector->withMockClient($mockClient);

    $resource = new SiteResource($connector);
    $result = $resource->getEnvironment(123, 456);

    expect($result)->toBe('APP_ENV=production');
    $mockClient->assertSent(GetEnvironmentRequest::class);
});

test('atualiza variáveis de ambiente do site', function (): void {
    $mockClient = new MockClient([
        UpdateEnvironmentRequest::class => MockResponse::make([]),
    ]);

    $connector = new ForgeConnector('test-token');
    $connector->withMockClient($mockClient);

    $resource = new SiteResource($connector);
    $resource->updateEnvironment(123, 456, 'APP_ENV=staging');

    $mockClient->assertSent(UpdateEnvironmentRequest::class);
});
