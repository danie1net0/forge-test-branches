<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\ForgeTestBranches;
use Ddr\ForgeTestBranches\Models\ReviewEnvironment;
use Ddr\ForgeTestBranches\Services\EnvironmentBuilder;
use Illuminate\Database\Eloquent\ModelNotFoundException;

beforeEach(function (): void {
    config(['forge-test-branches.forge_api_token' => 'fake-token']);
});

test('creates environment successfully', function (): void {
    $environment = new ReviewEnvironment([
        'branch' => 'feat/new',
        'slug' => 'feat-new',
        'domain' => 'feat-new.review.example.com',
        'server_id' => 123,
        'site_id' => 456,
        'database_id' => 789,
        'database_user_id' => 101,
    ]);

    $builder = Mockery::mock(EnvironmentBuilder::class);
    $builder->shouldReceive('create')
        ->once()
        ->with('feat/new')
        ->andReturn($environment);

    $forgeTestBranches = new ForgeTestBranches($builder);

    $result = $forgeTestBranches->create('feat/new');

    expect($result)->toBeInstanceOf(ReviewEnvironment::class)
        ->and($result->branch)->toBe('feat/new');
});

test('destroys environment successfully', function (): void {
    $environment = ReviewEnvironment::query()->create([
        'branch' => 'feat/to-destroy',
        'slug' => 'feat-to-destroy',
        'domain' => 'feat-to-destroy.review.example.com',
        'server_id' => 123,
        'site_id' => 456,
        'database_id' => 789,
        'database_user_id' => 101,
    ]);

    $builder = Mockery::mock(EnvironmentBuilder::class);
    $builder->shouldReceive('destroy')
        ->once()
        ->withArgs(fn ($env): bool => $env->id === $environment->id);

    $forgeTestBranches = new ForgeTestBranches($builder);
    $forgeTestBranches->destroy('feat/to-destroy');

    expect(true)->toBeTrue();
});

test('throws exception when trying to destroy nonexistent environment', function (): void {
    $builder = Mockery::mock(EnvironmentBuilder::class);
    $forgeTestBranches = new ForgeTestBranches($builder);

    $forgeTestBranches->destroy('feat/nonexistent');
})->throws(ModelNotFoundException::class);

test('deploys successfully', function (): void {
    $environment = ReviewEnvironment::query()->create([
        'branch' => 'feat/to-deploy',
        'slug' => 'feat-to-deploy',
        'domain' => 'feat-to-deploy.review.example.com',
        'server_id' => 123,
        'site_id' => 456,
        'database_id' => 789,
        'database_user_id' => 101,
    ]);

    $builder = Mockery::mock(EnvironmentBuilder::class);
    $builder->shouldReceive('deploy')
        ->once()
        ->withArgs(fn ($env): bool => $env->id === $environment->id);

    $forgeTestBranches = new ForgeTestBranches($builder);
    $forgeTestBranches->deploy('feat/to-deploy');

    expect(true)->toBeTrue();
});

test('throws exception when trying to deploy to nonexistent environment', function (): void {
    $builder = Mockery::mock(EnvironmentBuilder::class);
    $forgeTestBranches = new ForgeTestBranches($builder);

    $forgeTestBranches->deploy('feat/nonexistent');
})->throws(ModelNotFoundException::class);

test('finds existing environment', function (): void {
    $environment = ReviewEnvironment::query()->create([
        'branch' => 'feat/existing',
        'slug' => 'feat-existing',
        'domain' => 'feat-existing.review.example.com',
        'server_id' => 123,
        'site_id' => 456,
        'database_id' => 789,
        'database_user_id' => 101,
    ]);

    $builder = Mockery::mock(EnvironmentBuilder::class);
    $forgeTestBranches = new ForgeTestBranches($builder);

    $result = $forgeTestBranches->find('feat/existing');

    expect($result)->not->toBeNull()
        ->and($result->id)->toBe($environment->id);
});

test('returns null when environment does not exist', function (): void {
    $builder = Mockery::mock(EnvironmentBuilder::class);
    $forgeTestBranches = new ForgeTestBranches($builder);

    $result = $forgeTestBranches->find('feat/nonexistent');

    expect($result)->toBeNull();
});

test('checks if environment exists', function (): void {
    ReviewEnvironment::query()->create([
        'branch' => 'feat/existing',
        'slug' => 'feat-existing',
        'domain' => 'feat-existing.review.example.com',
        'server_id' => 123,
        'site_id' => 456,
        'database_id' => 789,
        'database_user_id' => 101,
    ]);

    $builder = Mockery::mock(EnvironmentBuilder::class);
    $forgeTestBranches = new ForgeTestBranches($builder);

    expect($forgeTestBranches->exists('feat/existing'))->toBeTrue()
        ->and($forgeTestBranches->exists('feat/nonexistent'))->toBeFalse();
});
