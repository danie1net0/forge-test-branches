<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\{CreateSiteData, SiteData};
use Ddr\ForgeTestBranches\Integrations\Forge\ForgeConnector;
use Ddr\ForgeTestBranches\Integrations\Forge\Requests\Sites\CreateSiteRequest;
use Saloon\Http\Faking\{MockClient, MockResponse};

test('resolve endpoint corretamente', function (): void {
    $request = new CreateSiteRequest(123, new CreateSiteData(name: 'test.example.com', type: 'php'));

    expect($request->resolveEndpoint())->toBe('/servers/123/sites');
});

test('usa método POST', function (): void {
    $request = new CreateSiteRequest(123, new CreateSiteData(name: 'test.example.com', type: 'php'));

    expect($request->getMethod()->value)->toBe('POST');
});

test('envia repositório junto com a criação do site', function (): void {
    $request = new CreateSiteRequest(123, new CreateSiteData(
        name: 'test.example.com',
        type: 'laravel',
        sourceControlProvider: 'gitlab',
        repository: 'user/repo',
        branch: 'feat/test',
        installComposerDependencies: true,
    ));

    expect($request->body()->all())->toBe([
        'name' => 'test.example.com',
        'type' => 'laravel',
        'domain_mode' => 'custom',
        'www_redirect_type' => 'none',
        'allow_wildcard_subdomains' => false,
        'source_control_provider' => 'gitlab',
        'repository' => 'user/repo',
        'branch' => 'feat/test',
        'install_composer_dependencies' => true,
        'zero_downtime_deployments' => false,
    ]);
});

test('não envia zero-downtime deployments ligado por padrão', function (): void {
    $request = new CreateSiteRequest(123, new CreateSiteData(name: 'test.example.com', type: 'php'));

    expect($request->body()->all())->toHaveKey('zero_downtime_deployments', false);
});

test('envia redirecionamento www e subdomínios curinga exigidos pela API', function (): void {
    $request = new CreateSiteRequest(123, new CreateSiteData(name: 'test.example.com', type: 'php'));

    expect($request->body()->all())
        ->toHaveKey('www_redirect_type', 'none')
        ->toHaveKey('allow_wildcard_subdomains', false);
});

test('cria site e retorna DTO correto', function (): void {
    $mockClient = new MockClient([
        CreateSiteRequest::class => MockResponse::make(forgeDocument(forgeResource('sites', 100, forgeSiteAttributes('test.example.com', [
            'status' => 'creating',
            'repository' => ['provider' => 'gitlab', 'url' => 'user/repo', 'branch' => 'feat/test', 'status' => 'installing'],
        ]))), 202),
    ]);

    $connector = new ForgeConnector('test-token', 'test-org');
    $connector->withMockClient($mockClient);

    $request = new CreateSiteRequest(123, new CreateSiteData(name: 'test.example.com', type: 'php'));
    $response = $connector->send($request);
    $result = $request->createDtoFromResponse($response);

    expect($result)->toBeInstanceOf(SiteData::class)
        ->id->toBe(100)
        ->serverId->toBe(123)
        ->name->toBe('test.example.com')
        ->status->toBe('creating')
        ->repositoryBranch->toBe('feat/test')
        ->repositoryStatus->toBe('installing');
});
