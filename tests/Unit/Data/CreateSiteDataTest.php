<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\CreateSiteData;

test('cria instância com parâmetros obrigatórios', function (): void {
    $data = new CreateSiteData(
        name: 'test.example.com',
        type: 'php',
    );

    expect($data)
        ->name->toBe('test.example.com')
        ->type->toBe('php')
        ->domainMode->toBe('custom')
        ->wwwRedirectType->toBe('none')
        ->allowWildcardSubdomains->toBeFalse()
        ->webDirectory->toBeNull()
        ->repository->toBeNull()
        ->zeroDowntimeDeployments->toBeFalse();
});

test('cria instância com todos os parâmetros', function (): void {
    $data = new CreateSiteData(
        name: 'test.example.com',
        type: 'laravel',
        domainMode: 'custom',
        wwwRedirectType: 'from-www',
        allowWildcardSubdomains: true,
        webDirectory: '/public',
        isIsolated: true,
        phpVersion: 'php84',
        sourceControlProvider: 'github',
        repository: 'user/repo',
        branch: 'main',
        installComposerDependencies: true,
        zeroDowntimeDeployments: true,
        nginxTemplateId: 1,
    );

    expect($data)
        ->name->toBe('test.example.com')
        ->type->toBe('laravel')
        ->wwwRedirectType->toBe('from-www')
        ->allowWildcardSubdomains->toBeTrue()
        ->webDirectory->toBe('/public')
        ->isIsolated->toBeTrue()
        ->phpVersion->toBe('php84')
        ->sourceControlProvider->toBe('github')
        ->repository->toBe('user/repo')
        ->branch->toBe('main')
        ->installComposerDependencies->toBeTrue()
        ->zeroDowntimeDeployments->toBeTrue()
        ->nginxTemplateId->toBe(1);
});

test('filtra valores null no toArray mas mantém os campos exigidos pela API', function (): void {
    $data = new CreateSiteData(
        name: 'test.example.com',
        type: 'php',
        webDirectory: '/public',
    );

    expect($data->toArray())->toBe([
        'name' => 'test.example.com',
        'type' => 'php',
        'domain_mode' => 'custom',
        'www_redirect_type' => 'none',
        'allow_wildcard_subdomains' => false,
        'web_directory' => '/public',
        'zero_downtime_deployments' => false,
    ]);
});

test('serializa campos com nomes da API v2', function (): void {
    $data = new CreateSiteData(
        name: 'test.example.com',
        type: 'laravel',
        webDirectory: '/public',
        isIsolated: false,
        phpVersion: 'php84',
        sourceControlProvider: 'gitlab',
        repository: 'user/repo',
        branch: 'main',
        installComposerDependencies: true,
    );

    expect($data->toArray())
        ->toHaveKey('web_directory', '/public')
        ->toHaveKey('is_isolated', false)
        ->toHaveKey('php_version', 'php84')
        ->toHaveKey('source_control_provider', 'gitlab')
        ->toHaveKey('repository', 'user/repo')
        ->toHaveKey('branch', 'main')
        ->toHaveKey('install_composer_dependencies', true)
        ->toHaveKey('zero_downtime_deployments', false);
});
