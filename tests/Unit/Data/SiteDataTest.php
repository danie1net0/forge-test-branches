<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\SiteData;

test('creates instance with all parameters', function (): void {
    $data = new SiteData(
        id: 456,
        serverId: 123,
        name: 'test.example.com',
        status: 'installed',
        url: 'https://test.example.com',
        user: 'forge',
        webDirectory: '/public',
        phpVersion: 'php84',
        quickDeploy: true,
        isolated: false,
        repositoryProvider: 'gitlab',
        repositoryUrl: 'user/repo',
        repositoryBranch: 'main',
        repositoryStatus: 'installed',
        createdAt: '2025-07-29T09:00:00Z',
    );

    expect($data)
        ->id->toBe(456)
        ->serverId->toBe(123)
        ->name->toBe('test.example.com')
        ->getName()->toBe('test.example.com')
        ->webDirectory->toBe('/public')
        ->repositoryUrl->toBe('user/repo')
        ->repositoryProvider->toBe('gitlab')
        ->quickDeploy->toBeTrue()
        ->phpVersion->toBe('php84');
});

test('mapeia atributos aninhados do repositório', function (): void {
    $data = SiteData::from([
        'id' => 456,
        'server_id' => 123,
        'name' => 'test.example.com',
        'status' => 'installed',
        'quick_deploy' => false,
        'repository' => [
            'provider' => 'github',
            'url' => 'user/repo',
            'branch' => 'feat/test',
            'status' => 'installing',
        ],
    ]);

    expect($data)
        ->repositoryProvider->toBe('github')
        ->repositoryUrl->toBe('user/repo')
        ->repositoryBranch->toBe('feat/test')
        ->repositoryStatus->toBe('installing')
        ->quickDeploy->toBeFalse();
});

test('indica se o site e o repositório terminaram de instalar', function (string $status, ?string $repositoryStatus, bool $expected): void {
    $data = new SiteData(
        id: 456,
        serverId: 123,
        name: 'test.example.com',
        status: $status,
        repositoryStatus: $repositoryStatus,
    );

    expect($data->isInstalled())->toBe($expected);
})->with([
    'instalado' => ['installed', 'installed', true],
    'nunca publicado' => ['never-deployed', 'installed', true],
    'publicado' => ['deployed', 'installed', true],
    'deploy em andamento não bloqueia' => ['deploying', 'installed', true],
    'em manutenção' => ['maintenance', 'installed', true],
    'criando' => ['creating', null, false],
    'instalando' => ['installing', null, false],
    'repositório instalando' => ['installed', 'installing', false],
    'sem repositório ainda' => ['installed', null, false],
    'repositório sendo removido' => ['installed', 'removing', false],
    'falhou mesmo com repositório instalado' => ['failed', 'installed', false],
]);

test('reconhece status desconhecido como ainda não instalado', function (): void {
    $data = new SiteData(id: 456, serverId: 123, name: 'test.example.com', status: 'some-future-status', repositoryStatus: 'installed');

    expect($data->isInstalled())->toBeFalse();
});

test('indica quando a instalação do site falhou', function (string $status, bool $expected): void {
    $data = new SiteData(id: 456, serverId: 123, name: 'test.example.com', status: $status);

    expect($data->hasFailedInstallation())->toBe($expected);
})->with([
    'falhou' => ['failed', true],
    'sendo removido' => ['removing', true],
    'sendo desinstalado' => ['uninstalling', true],
    'instalado' => ['installed', false],
    'instalando' => ['installing', false],
    'em deploy' => ['deploying', false],
]);

test('não considera falha um status desconhecido', function (): void {
    $data = new SiteData(id: 456, serverId: 123, name: 'test.example.com', status: 'some-future-status');

    expect($data->hasFailedInstallation())->toBeFalse();
});

test('indica se o site está sendo removido', function (string $status, bool $expected): void {
    $data = new SiteData(id: 456, serverId: 123, name: 'test.example.com', status: $status);

    expect($data->isBeingRemoved())->toBe($expected);
})->with([
    'sendo removido' => ['removing', true],
    'sendo desinstalado' => ['uninstalling', true],
    'instalado' => ['installed', false],
    'falhou' => ['failed', false],
]);
