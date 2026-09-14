<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\{CreateSiteData, SiteData};
use Ddr\ForgeTestBranches\Exceptions\{ResourceFailedException, ResourceTimeoutException};
use Ddr\ForgeTestBranches\Integrations\Forge\ForgeConnector;
use Ddr\ForgeTestBranches\Integrations\Forge\Requests\Sites\{CreateSiteRequest, DeleteSiteRequest, DeploySiteRequest, EnableQuickDeployRequest, GetEnvironmentFileRequest, GetSiteRequest, ListSitesRequest, UpdateDeploymentScriptRequest, UpdateEnvironmentFileRequest};
use Ddr\ForgeTestBranches\Integrations\Forge\Resources\SiteResource;
use Illuminate\Support\Sleep;
use Saloon\Http\Faking\{MockClient, MockResponse};

function makeSiteResource(MockClient $mockClient): SiteResource
{
    $connector = new ForgeConnector('test-token', 'test-org');
    $connector->withMockClient($mockClient);

    return new SiteResource($connector);
}

test('lista sites do servidor', function (): void {
    $mockClient = new MockClient([
        ListSitesRequest::class => MockResponse::make(forgeCollection([
            forgeResource('sites', 1, forgeSiteAttributes('site1.example.com')),
            forgeResource('sites', 2, forgeSiteAttributes('site2.example.com')),
        ])),
    ]);

    $result = makeSiteResource($mockClient)->list(123);

    expect($result)->toHaveCount(2)
        ->and($result[0])->toBeInstanceOf(SiteData::class)
        ->id->toBe(1)
        ->name->toBe('site1.example.com')
        ->and($result[1])->toBeInstanceOf(SiteData::class)
        ->id->toBe(2);
});

test('obtém site por id', function (): void {
    $mockClient = new MockClient([
        GetSiteRequest::class => MockResponse::make(forgeDocument(forgeResource('sites', 456, forgeSiteAttributes('test.example.com')))),
    ]);

    $result = makeSiteResource($mockClient)->get(123, 456);

    expect($result)->toBeInstanceOf(SiteData::class)
        ->id->toBe(456)
        ->serverId->toBe(123)
        ->name->toBe('test.example.com');
});

test('encontra site pelo domínio usando filtro por nome', function (): void {
    $mockClient = new MockClient([
        ListSitesRequest::class => MockResponse::make(forgeCollection([
            forgeResource('sites', 1, forgeSiteAttributes('sub.target.example.com')),
            forgeResource('sites', 2, forgeSiteAttributes('target.example.com')),
        ])),
    ]);

    $result = makeSiteResource($mockClient)->findByName(123, 'target.example.com');

    expect($result)->toBeInstanceOf(SiteData::class)
        ->id->toBe(2)
        ->name->toBe('target.example.com')
        ->and($mockClient->getLastPendingRequest()->query()->get('filter[name]'))->toBe('target.example.com');
});

test('retorna null quando site não é encontrado pelo domínio', function (): void {
    $mockClient = new MockClient([
        ListSitesRequest::class => MockResponse::make(forgeCollection([])),
    ]);

    expect(makeSiteResource($mockClient)->findByName(123, 'nonexistent.example.com'))->toBeNull();
});

test('aguarda instalação do site e do repositório, ignorando o status de deploy', function (): void {
    Sleep::fake();

    $mockClient = new MockClient([
        MockResponse::make(forgeDocument(forgeResource('sites', 100, forgeSiteAttributes('test.example.com', ['status' => 'creating'])))),
        MockResponse::make(forgeDocument(forgeResource('sites', 100, forgeSiteAttributes('test.example.com', [
            'repository' => ['provider' => 'gitlab', 'url' => 'user/repo', 'branch' => 'main', 'status' => 'installing'],
        ])))),
        MockResponse::make(forgeDocument(forgeResource('sites', 100, forgeSiteAttributes('test.example.com', [
            'status' => 'deploying',
            'deployment_status' => 'deploying',
        ])))),
    ]);

    $result = makeSiteResource($mockClient)->waitForInstallation(123, 100, 5, 2);

    expect($result)->toBeInstanceOf(SiteData::class)
        ->status->toBe('deploying')
        ->repositoryStatus->toBe('installed');

    $mockClient->assertSentCount(3);
    Sleep::assertSequence([Sleep::for(2)->seconds(), Sleep::for(2)->seconds()]);
});

test('lança exceção quando instalação do site expira', function (): void {
    Sleep::fake();

    $mockClient = new MockClient([
        MockResponse::make(forgeDocument(forgeResource('sites', 100, forgeSiteAttributes('test.example.com', ['status' => 'installing'])))),
        MockResponse::make(forgeDocument(forgeResource('sites', 100, forgeSiteAttributes('test.example.com', ['status' => 'installing'])))),
        MockResponse::make(forgeDocument(forgeResource('sites', 100, forgeSiteAttributes('test.example.com', ['status' => 'installing'])))),
    ]);

    $action = fn (): SiteData => makeSiteResource($mockClient)->waitForInstallation(123, 100, 3, 1);

    expect($action)->toThrow(ResourceTimeoutException::class, 'Timeout waiting for site installation (site 100) after 3 attempts');
    $mockClient->assertSentCount(3);
});

test('lança exceção imediatamente quando a instalação do site falha', function (string $status): void {
    Sleep::fake();

    $mockClient = new MockClient([
        MockResponse::make(forgeDocument(forgeResource('sites', 100, forgeSiteAttributes('test.example.com', ['status' => $status])))),
    ]);

    $action = fn (): SiteData => makeSiteResource($mockClient)->waitForInstallation(123, 100, 10, 5);

    expect($action)->toThrow(ResourceFailedException::class);
    $mockClient->assertSentCount(1);
    Sleep::assertNeverSlept();
})->with([
    'falhou' => ['failed'],
    'sendo removido' => ['removing'],
    'sendo desinstalado' => ['uninstalling'],
]);

test('cria site e retorna SiteData', function (): void {
    $mockClient = new MockClient([
        CreateSiteRequest::class => MockResponse::make(forgeDocument(forgeResource('sites', 200, forgeSiteAttributes('new.example.com', ['status' => 'creating']))), 202),
    ]);

    $result = makeSiteResource($mockClient)->create(123, new CreateSiteData(name: 'new.example.com', type: 'php'));

    expect($result)->toBeInstanceOf(SiteData::class)
        ->id->toBe(200)
        ->name->toBe('new.example.com')
        ->status->toBe('creating');
});

test('deleta site com sucesso', function (): void {
    $mockClient = new MockClient([
        DeleteSiteRequest::class => MockResponse::make('', 202),
    ]);

    makeSiteResource($mockClient)->delete(123, 456);

    $mockClient->assertSent(DeleteSiteRequest::class);
    expect($mockClient->getLastPendingRequest()->getUrl())->toBe('https://forge.laravel.com/api/orgs/test-org/servers/123/sites/456');
});

test('executa deploy do site com sucesso', function (): void {
    $mockClient = new MockClient([
        DeploySiteRequest::class => MockResponse::make(forgeDocument(forgeResource('deployments', 99, ['status' => 'queued'])), 202),
    ]);

    makeSiteResource($mockClient)->deploy(123, 456);

    $mockClient->assertSent(DeploySiteRequest::class);
    expect($mockClient->getLastPendingRequest()->getUrl())->toBe('https://forge.laravel.com/api/orgs/test-org/servers/123/sites/456/deployments');
});

test('atualiza script de deploy', function (): void {
    $mockClient = new MockClient([
        UpdateDeploymentScriptRequest::class => MockResponse::make(forgeDocument(forgeResource('deploymentScripts', 456, ['content' => 'git pull', 'auto_source' => false]))),
    ]);

    makeSiteResource($mockClient)->updateDeploymentScript(123, 456, 'cd /home/forge && git pull');

    $mockClient->assertSent(UpdateDeploymentScriptRequest::class);
    expect($mockClient->getLastPendingRequest())
        ->getUrl()->toBe('https://forge.laravel.com/api/orgs/test-org/servers/123/sites/456/deployments/script')
        ->body()->all()->toBe(['content' => 'cd /home/forge && git pull']);
});

test('habilita push to deploy', function (): void {
    $mockClient = new MockClient([
        EnableQuickDeployRequest::class => MockResponse::make('', 202),
    ]);

    makeSiteResource($mockClient)->enableQuickDeploy(123, 456);

    $mockClient->assertSent(EnableQuickDeployRequest::class);
    expect($mockClient->getLastPendingRequest()->getUrl())->toBe('https://forge.laravel.com/api/orgs/test-org/servers/123/sites/456/deployments/push-to-deploy');
});

test('obtém o arquivo .env do site', function (): void {
    $mockClient = new MockClient([
        GetEnvironmentFileRequest::class => MockResponse::make(forgeDocument(forgeResource('environments', 456, ['content' => 'APP_ENV=production']))),
    ]);

    $result = makeSiteResource($mockClient)->getEnvironmentFile(123, 456);

    expect($result)->toBe('APP_ENV=production')
        ->and($mockClient->getLastPendingRequest()->getUrl())->toBe('https://forge.laravel.com/api/orgs/test-org/servers/123/sites/456/environment');
});

test('atualiza o arquivo .env do site', function (): void {
    $mockClient = new MockClient([
        UpdateEnvironmentFileRequest::class => MockResponse::make('', 202),
    ]);

    makeSiteResource($mockClient)->updateEnvironmentFile(123, 456, 'APP_ENV=staging');

    $mockClient->assertSent(UpdateEnvironmentFileRequest::class);
    expect($mockClient->getLastPendingRequest())
        ->getMethod()->value->toBe('PUT')
        ->body()->all()->toBe(['environment' => 'APP_ENV=staging']);
});

test('aguarda o arquivo .env conter o valor esperado antes de prosseguir', function (): void {
    Sleep::fake();

    $mockClient = new MockClient([
        MockResponse::make(forgeDocument(forgeResource('environments', 456, ['content' => 'APP_ENV=old']))),
        MockResponse::make(forgeDocument(forgeResource('environments', 456, ['content' => "APP_ENV=staging\nDB_DATABASE=review_feat"]))),
    ]);

    $result = makeSiteResource($mockClient)->waitForEnvironmentFileContaining(123, 456, 'DB_DATABASE=review_feat', 5, 1);

    expect($result)->toContain('DB_DATABASE=review_feat');
});

test('lança timeout se o arquivo .env nunca refletir a escrita', function (): void {
    Sleep::fake();

    $mockClient = new MockClient([
        GetEnvironmentFileRequest::class => MockResponse::make(forgeDocument(forgeResource('environments', 456, ['content' => 'APP_ENV=old']))),
    ]);

    $action = fn (): string => makeSiteResource($mockClient)->waitForEnvironmentFileContaining(123, 456, 'DB_DATABASE=review_feat', 2, 1);

    expect($action)->toThrow(ResourceTimeoutException::class);
});
