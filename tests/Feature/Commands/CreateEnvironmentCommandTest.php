<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Models\ReviewEnvironment;
use Ddr\ForgeTestBranches\Services\EnvironmentBuilder;

beforeEach(function (): void {
    config([
        'forge-test-branches.forge_api_token' => 'fake-token',
        'forge-test-branches.branch.patterns' => ['*'],
    ]);
});

test('displays error when branch is not specified', function (): void {
    $this->artisan('forge-test-branches:create')
        ->expectsOutput('Branch not specified. Use --branch=branch-name or set CI_COMMIT_REF_NAME')
        ->assertExitCode(1);
});

test('displays warning when environment already exists', function (): void {
    ReviewEnvironment::query()->create([
        'branch' => 'feat/existing',
        'slug' => 'feat-existing',
        'domain' => 'feat-existing.review.example.com',
        'server_id' => 123,
        'site_id' => 456,
        'database_id' => 789,
        'database_user_id' => 101,
    ]);

    $this->artisan('forge-test-branches:create', ['--branch' => 'feat/existing'])
        ->expectsOutput('Environment already exists for branch: feat/existing')
        ->expectsOutput('URL: https://feat-existing.review.example.com')
        ->assertExitCode(0);
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

    $this->app->instance(EnvironmentBuilder::class, $builder);

    $this->artisan('forge-test-branches:create', ['--branch' => 'feat/new'])
        ->expectsOutput('Creating environment for branch: feat/new')
        ->expectsOutput('Environment created successfully!')
        ->expectsOutput('URL: https://feat-new.review.example.com')
        ->assertExitCode(0);
});

test('displays error when environment creation fails', function (): void {
    $builder = Mockery::mock(EnvironmentBuilder::class);
    $builder->shouldReceive('create')
        ->once()
        ->with('feat/error')
        ->andThrow(new RuntimeException('API Error'));

    $this->app->instance(EnvironmentBuilder::class, $builder);

    $this->artisan('forge-test-branches:create', ['--branch' => 'feat/error'])
        ->expectsOutput('Creating environment for branch: feat/error')
        ->expectsOutput('Error creating environment: API Error')
        ->assertExitCode(1);
});

test('displays warning when branch does not match allowed patterns', function (): void {
    config(['forge-test-branches.branch.patterns' => ['feat/*', 'fix/*']]);

    $this->artisan('forge-test-branches:create', ['--branch' => 'main'])
        ->expectsOutput('Branch does not match allowed patterns: main')
        ->assertExitCode(0);
});
