<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\InstallGitRepositoryData;

test('cria instância com parâmetros obrigatórios', function (): void {
    $data = new InstallGitRepositoryData(
        provider: 'gitlab',
        repository: 'user/repo',
    );

    expect($data)
        ->provider->toBe('gitlab')
        ->repository->toBe('user/repo')
        ->branch->toBeNull()
        ->composer->toBeNull();
});

test('cria instância com todos os parâmetros', function (): void {
    $data = new InstallGitRepositoryData(
        provider: 'github',
        repository: 'user/repo',
        branch: 'main',
        composer: true,
    );

    expect($data)
        ->provider->toBe('github')
        ->repository->toBe('user/repo')
        ->branch->toBe('main')
        ->composer->toBeTrue();
});

test('filtra valores null no toArray', function (): void {
    $data = new InstallGitRepositoryData(
        provider: 'gitlab',
        repository: 'user/repo',
    );

    expect($data->toArray())->toHaveKeys(['provider', 'repository'])
        ->not->toHaveKeys(['branch', 'composer']);
});

test('mantém todos os valores não-null no toArray', function (): void {
    $data = new InstallGitRepositoryData(
        provider: 'github',
        repository: 'user/repo',
        branch: 'main',
        composer: true,
    );

    expect($data->toArray())
        ->toHaveKey('provider', 'github')
        ->toHaveKey('repository', 'user/repo')
        ->toHaveKey('branch', 'main')
        ->toHaveKey('composer', true);
});
