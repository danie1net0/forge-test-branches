<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\InstallGitRepositoryData;

test('creates instance with required parameters', function (): void {
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

test('creates instance with all parameters', function (): void {
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
