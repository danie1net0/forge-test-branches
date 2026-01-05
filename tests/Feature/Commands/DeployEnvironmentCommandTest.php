<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\EnvironmentData;
use Ddr\ForgeTestBranches\Services\EnvironmentBuilder;

beforeEach(function (): void {
    config(['forge-test-branches.forge_api_token' => 'fake-token']);
});

function makeEnvData(string $branch, string $slug): EnvironmentData
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
    $this->artisan('forge-test-branches:deploy')
        ->expectsOutput('Branch not specified. Use --branch=branch-name or set CI_COMMIT_REF_NAME')
        ->assertExitCode(1);
});

test('displays error when environment does not exist', function (): void {
    $builder = Mockery::mock(EnvironmentBuilder::class);
    $builder->shouldReceive('find')
        ->once()
        ->with('feat/nonexistent')
        ->andReturnNull();

    $this->app->instance(EnvironmentBuilder::class, $builder);

    $this->artisan('forge-test-branches:deploy', ['--branch' => 'feat/nonexistent'])
        ->expectsOutput('Environment not found for branch: feat/nonexistent')
        ->assertExitCode(1);
});

test('deploys successfully', function (): void {
    $environment = makeEnvData('feat/to-deploy', 'feat-to-deploy');

    $builder = Mockery::mock(EnvironmentBuilder::class);
    $builder->shouldReceive('find')
        ->once()
        ->with('feat/to-deploy')
        ->andReturn($environment);
    $builder->shouldReceive('deploy')
        ->once()
        ->with($environment);

    $this->app->instance(EnvironmentBuilder::class, $builder);

    $this->artisan('forge-test-branches:deploy', ['--branch' => 'feat/to-deploy'])
        ->expectsOutput('Deploying to branch: feat/to-deploy')
        ->expectsOutput('Deploy started successfully!')
        ->assertExitCode(0);
});

test('displays error when deploy fails', function (): void {
    $environment = makeEnvData('feat/error', 'feat-error');

    $builder = Mockery::mock(EnvironmentBuilder::class);
    $builder->shouldReceive('find')
        ->once()
        ->with('feat/error')
        ->andReturn($environment);
    $builder->shouldReceive('deploy')
        ->once()
        ->andThrow(new RuntimeException('API Error'));

    $this->app->instance(EnvironmentBuilder::class, $builder);

    $this->artisan('forge-test-branches:deploy', ['--branch' => 'feat/error'])
        ->expectsOutput('Deploying to branch: feat/error')
        ->expectsOutput('Error deploying: API Error')
        ->assertExitCode(1);
});
