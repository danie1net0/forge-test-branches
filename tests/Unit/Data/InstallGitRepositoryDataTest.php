<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\InstallGitRepositoryData;

test('creates instance with required parameters', function (): void {
    $data = new InstallGitRepositoryData(
        provider: 'gitlab',
        repository: 'user/repo',
    );

    expect($data->provider)->toBe('gitlab')
        ->and($data->repository)->toBe('user/repo')
        ->and($data->branch)->toBeNull()
        ->and($data->composer)->toBeNull();
});

test('creates instance with all parameters', function (): void {
    $data = new InstallGitRepositoryData(
        provider: 'github',
        repository: 'user/repo',
        branch: 'main',
        composer: true,
    );

    expect($data->provider)->toBe('github')
        ->and($data->repository)->toBe('user/repo')
        ->and($data->branch)->toBe('main')
        ->and($data->composer)->toBeTrue();
});
