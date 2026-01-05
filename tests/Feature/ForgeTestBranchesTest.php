<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\EnvironmentData;
use Ddr\ForgeTestBranches\ForgeTestBranches;
use Ddr\ForgeTestBranches\Services\EnvironmentBuilder;

beforeEach(function (): void {
    config(['forge-test-branches.forge_api_token' => 'fake-token']);
});

function makeEnvironment(string $branch = 'feat/new', string $slug = 'feat-new'): EnvironmentData
{
    return new EnvironmentData(
        branch: $branch,
        slug: $slug,
        domain: "{$slug}.review.example.com",
        serverId: 123,
        siteId: 456,
        databaseId: 789,
        databaseUserId: 101,
    );
}

test('creates environment successfully', function (): void {
    $environment = makeEnvironment();

    $builder = Mockery::mock(EnvironmentBuilder::class);
    $builder->shouldReceive('create')
        ->once()
        ->with('feat/new')
        ->andReturn($environment);

    $forgeTestBranches = new ForgeTestBranches($builder);
    $result = $forgeTestBranches->create('feat/new');

    expect($result)->toBeInstanceOf(EnvironmentData::class)
        ->branch->toBe('feat/new');
});

test('destroys environment successfully', function (): void {
    $environment = makeEnvironment('feat/to-destroy', 'feat-to-destroy');

    $builder = Mockery::mock(EnvironmentBuilder::class);
    $builder->shouldReceive('find')
        ->once()
        ->with('feat/to-destroy')
        ->andReturn($environment);
    $builder->shouldReceive('destroy')
        ->once()
        ->with($environment);

    $forgeTestBranches = new ForgeTestBranches($builder);
    $forgeTestBranches->destroy('feat/to-destroy');

    expect(true)->toBeTrue();
});

test('throws exception when trying to destroy nonexistent environment', function (): void {
    $builder = Mockery::mock(EnvironmentBuilder::class);
    $builder->shouldReceive('find')
        ->once()
        ->with('feat/nonexistent')
        ->andReturnNull();

    $forgeTestBranches = new ForgeTestBranches($builder);

    $forgeTestBranches->destroy('feat/nonexistent');
})->throws(RuntimeException::class, 'Environment not found for branch: feat/nonexistent');

test('deploys successfully', function (): void {
    $environment = makeEnvironment('feat/to-deploy', 'feat-to-deploy');

    $builder = Mockery::mock(EnvironmentBuilder::class);
    $builder->shouldReceive('find')
        ->once()
        ->with('feat/to-deploy')
        ->andReturn($environment);
    $builder->shouldReceive('deploy')
        ->once()
        ->with($environment);

    $forgeTestBranches = new ForgeTestBranches($builder);
    $forgeTestBranches->deploy('feat/to-deploy');

    expect(true)->toBeTrue();
});

test('throws exception when trying to deploy to nonexistent environment', function (): void {
    $builder = Mockery::mock(EnvironmentBuilder::class);
    $builder->shouldReceive('find')
        ->once()
        ->with('feat/nonexistent')
        ->andReturnNull();

    $forgeTestBranches = new ForgeTestBranches($builder);

    $forgeTestBranches->deploy('feat/nonexistent');
})->throws(RuntimeException::class, 'Environment not found for branch: feat/nonexistent');

test('finds existing environment', function (): void {
    $environment = makeEnvironment('feat/existing', 'feat-existing');

    $builder = Mockery::mock(EnvironmentBuilder::class);
    $builder->shouldReceive('find')
        ->once()
        ->with('feat/existing')
        ->andReturn($environment);

    $forgeTestBranches = new ForgeTestBranches($builder);
    $result = $forgeTestBranches->find('feat/existing');

    expect($result)->not->toBeNull()
        ->siteId->toBe(456);
});

test('returns null when environment does not exist', function (): void {
    $builder = Mockery::mock(EnvironmentBuilder::class);
    $builder->shouldReceive('find')
        ->once()
        ->with('feat/nonexistent')
        ->andReturnNull();

    $forgeTestBranches = new ForgeTestBranches($builder);
    $result = $forgeTestBranches->find('feat/nonexistent');

    expect($result)->toBeNull();
});

test('checks if environment exists', function (): void {
    $builder = Mockery::mock(EnvironmentBuilder::class);
    $builder->shouldReceive('exists')
        ->with('feat/existing')
        ->andReturnTrue();
    $builder->shouldReceive('exists')
        ->with('feat/nonexistent')
        ->andReturnFalse();

    $forgeTestBranches = new ForgeTestBranches($builder);

    expect($forgeTestBranches->exists('feat/existing'))->toBeTrue();
    expect($forgeTestBranches->exists('feat/nonexistent'))->toBeFalse();
});
