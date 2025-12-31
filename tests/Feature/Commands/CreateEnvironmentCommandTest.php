<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\EnvironmentData;
use Ddr\ForgeTestBranches\Services\EnvironmentBuilder;

beforeEach(function (): void {
    config([
        'forge-test-branches.forge_api_token' => 'fake-token',
        'forge-test-branches.branch.patterns' => ['*'],
    ]);
});

function makeEnvironmentData(string $branch, string $slug): EnvironmentData
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

test('exibe erro quando branch não é especificada', function (): void {
    $this->artisan('forge-test-branches:create')
        ->expectsOutput('Branch not specified. Use --branch=branch-name or set CI_COMMIT_REF_NAME')
        ->assertExitCode(1);
});

test('exibe aviso quando ambiente já existe', function (): void {
    $environment = makeEnvironmentData('feat/existing', 'feat-existing');

    $builder = Mockery::mock(EnvironmentBuilder::class);
    $builder->shouldReceive('find')
        ->once()
        ->with('feat/existing')
        ->andReturn($environment);

    $this->app->instance(EnvironmentBuilder::class, $builder);

    $this->artisan('forge-test-branches:create', ['--branch' => 'feat/existing'])
        ->expectsOutput('Environment already exists for branch: feat/existing')
        ->expectsOutput('URL: https://feat-existing.review.example.com')
        ->assertExitCode(0);
});

test('cria ambiente com sucesso', function (): void {
    $environment = makeEnvironmentData('feat/new', 'feat-new');

    $builder = Mockery::mock(EnvironmentBuilder::class);
    $builder->shouldReceive('find')
        ->once()
        ->with('feat/new')
        ->andReturnNull();
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

test('exibe erro quando criação do ambiente falha', function (): void {
    $builder = Mockery::mock(EnvironmentBuilder::class);
    $builder->shouldReceive('find')
        ->once()
        ->with('feat/error')
        ->andReturnNull();
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

test('exibe aviso quando branch não corresponde aos padrões permitidos', function (): void {
    config(['forge-test-branches.branch.patterns' => ['feat/*', 'fix/*']]);

    $this->artisan('forge-test-branches:create', ['--branch' => 'main'])
        ->expectsOutput('Branch does not match allowed patterns: main')
        ->assertExitCode(0);
});
