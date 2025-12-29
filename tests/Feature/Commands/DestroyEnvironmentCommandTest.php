<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Models\ReviewEnvironment;
use Ddr\ForgeTestBranches\Services\EnvironmentBuilder;

beforeEach(function (): void {
    config(['forge-test-branches.forge_api_token' => 'fake-token']);
});

test('displays error when branch is not specified', function (): void {
    $this->artisan('forge-test-branches:destroy')
        ->expectsOutput('Branch not specified. Use --branch=branch-name or set CI_COMMIT_REF_NAME')
        ->assertExitCode(1);
});

test('displays warning when environment does not exist', function (): void {
    $this->artisan('forge-test-branches:destroy', ['--branch' => 'feat/nonexistent'])
        ->expectsOutput('Environment not found for branch: feat/nonexistent')
        ->assertExitCode(0);
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

    $this->app->instance(EnvironmentBuilder::class, $builder);

    $this->artisan('forge-test-branches:destroy', ['--branch' => 'feat/to-destroy'])
        ->expectsOutput('Destroying environment for branch: feat/to-destroy')
        ->expectsOutput('Environment destroyed successfully!')
        ->assertExitCode(0);
});

test('displays error when environment destruction fails', function (): void {
    ReviewEnvironment::query()->create([
        'branch' => 'feat/error',
        'slug' => 'feat-error',
        'domain' => 'feat-error.review.example.com',
        'server_id' => 123,
        'site_id' => 456,
        'database_id' => 789,
        'database_user_id' => 101,
    ]);

    $builder = Mockery::mock(EnvironmentBuilder::class);
    $builder->shouldReceive('destroy')
        ->once()
        ->andThrow(new RuntimeException('API Error'));

    $this->app->instance(EnvironmentBuilder::class, $builder);

    $this->artisan('forge-test-branches:destroy', ['--branch' => 'feat/error'])
        ->expectsOutput('Destroying environment for branch: feat/error')
        ->expectsOutput('Error destroying environment: API Error')
        ->assertExitCode(1);
});
