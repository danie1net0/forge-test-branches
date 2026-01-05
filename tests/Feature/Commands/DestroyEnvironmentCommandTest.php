<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\EnvironmentData;
use Ddr\ForgeTestBranches\Services\EnvironmentBuilder;

beforeEach(function (): void {
    config(['forge-test-branches.forge_api_token' => 'fake-token']);
});

function makeDestroyEnvData(string $branch, string $slug): EnvironmentData
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

test('displays error when branch is not specified', function (): void {
    $this->artisan('forge-test-branches:destroy')
        ->expectsOutput('Branch not specified. Use --branch=branch-name or set CI_COMMIT_REF_NAME')
        ->assertExitCode(1);
});

test('displays warning when environment does not exist', function (): void {
    $builder = Mockery::mock(EnvironmentBuilder::class);
    $builder->shouldReceive('find')
        ->once()
        ->with('feat/nonexistent')
        ->andReturnNull();

    $this->app->instance(EnvironmentBuilder::class, $builder);

    $this->artisan('forge-test-branches:destroy', ['--branch' => 'feat/nonexistent'])
        ->expectsOutput('Environment not found for branch: feat/nonexistent')
        ->assertExitCode(0);
});

test('destroys environment successfully', function (): void {
    $environment = makeDestroyEnvData('feat/to-destroy', 'feat-to-destroy');

    $builder = Mockery::mock(EnvironmentBuilder::class);
    $builder->shouldReceive('find')
        ->once()
        ->with('feat/to-destroy')
        ->andReturn($environment);
    $builder->shouldReceive('destroy')
        ->once()
        ->with($environment);

    $this->app->instance(EnvironmentBuilder::class, $builder);

    $this->artisan('forge-test-branches:destroy', ['--branch' => 'feat/to-destroy'])
        ->expectsOutput('Destroying environment for branch: feat/to-destroy')
        ->expectsOutput('Environment destroyed successfully!')
        ->assertExitCode(0);
});

test('displays error when environment destruction fails', function (): void {
    $environment = makeDestroyEnvData('feat/error', 'feat-error');

    $builder = Mockery::mock(EnvironmentBuilder::class);
    $builder->shouldReceive('find')
        ->once()
        ->with('feat/error')
        ->andReturn($environment);
    $builder->shouldReceive('destroy')
        ->once()
        ->andThrow(new RuntimeException('API Error'));

    $this->app->instance(EnvironmentBuilder::class, $builder);

    $this->artisan('forge-test-branches:destroy', ['--branch' => 'feat/error'])
        ->expectsOutput('Destroying environment for branch: feat/error')
        ->expectsOutput('Error destroying environment: API Error')
        ->assertExitCode(1);
});
