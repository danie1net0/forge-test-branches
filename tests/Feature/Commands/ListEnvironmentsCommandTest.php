<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Process;
use Ddr\ForgeTestBranches\Data\EnvironmentData;
use Ddr\ForgeTestBranches\Services\EnvironmentBuilder;

beforeEach(function (): void {
    config(['forge-test-branches.forge_api_token' => 'fake-token']);
});

function makeListEnvData(string $branch, string $slug, int $siteId = 456): EnvironmentData
{
    return new EnvironmentData(
        branch: $branch,
        slug: $slug,
        domain: "{$slug}.review.example.com",
        serverId: 123,
        siteId: $siteId,
    );
}

test('exibe mensagem quando não há ambientes', function (): void {
    $builder = Mockery::mock(EnvironmentBuilder::class);
    $builder->shouldReceive('listAll')
        ->once()
        ->andReturn([]);

    $this->app->instance(EnvironmentBuilder::class, $builder);

    $this->artisan('forge-test-branches:list')
        ->expectsOutput('No review environments found.')
        ->assertExitCode(0);
});

test('lista todos os ambientes com status real', function (): void {
    $builder = Mockery::mock(EnvironmentBuilder::class);
    $builder->shouldReceive('listAll')
        ->once()
        ->andReturn([
            makeListEnvData('feat/login', 'feat-login', 100),
            makeListEnvData('fix/bug-123', 'fix-bug-123', 200),
        ]);

    $this->app->instance(EnvironmentBuilder::class, $builder);

    Process::fake([
        'git ls-remote --heads *' => Process::result(
            output: "abc123\trefs/heads/feat/login\nabc456\trefs/heads/fix/bug-123\n"
        ),
    ]);

    $this->artisan('forge-test-branches:list')
        ->expectsTable(
            ['Branch', 'Domain', 'Status', 'Site ID'],
            [
                ['feat/login', 'feat-login.review.example.com', 'Active', 100],
                ['fix/bug-123', 'fix-bug-123.review.example.com', 'Active', 200],
            ]
        )
        ->assertExitCode(0);
});

test('mostra ambientes órfãos e ativos na listagem padrão', function (): void {
    $builder = Mockery::mock(EnvironmentBuilder::class);
    $builder->shouldReceive('listAll')
        ->once()
        ->andReturn([
            makeListEnvData('feat/login', 'feat-login', 100),
            makeListEnvData('feat/removed', 'feat-removed', 200),
        ]);

    $this->app->instance(EnvironmentBuilder::class, $builder);

    Process::fake([
        'git ls-remote --heads *' => Process::result(
            output: "abc123\trefs/heads/feat/login\n"
        ),
    ]);

    $this->artisan('forge-test-branches:list')
        ->expectsTable(
            ['Branch', 'Domain', 'Status', 'Site ID'],
            [
                ['feat/login', 'feat-login.review.example.com', 'Active', 100],
                ['feat/removed', 'feat-removed.review.example.com', 'Orphan', 200],
            ]
        )
        ->assertExitCode(0);
});

test('exibe erro quando falha ao buscar ambientes', function (): void {
    $builder = Mockery::mock(EnvironmentBuilder::class);
    $builder->shouldReceive('listAll')
        ->once()
        ->andThrow(new RuntimeException('API Error'));

    $this->app->instance(EnvironmentBuilder::class, $builder);

    $this->artisan('forge-test-branches:list')
        ->expectsOutput('Error fetching environments: API Error')
        ->assertExitCode(1);
});

test('exibe mensagem quando não há ambientes órfãos', function (): void {
    $builder = Mockery::mock(EnvironmentBuilder::class);
    $builder->shouldReceive('listAll')
        ->once()
        ->andReturn([
            makeListEnvData('feat/login', 'feat-login', 100),
        ]);

    $this->app->instance(EnvironmentBuilder::class, $builder);

    Process::fake([
        'git ls-remote --heads *' => Process::result(
            output: "abc123\trefs/heads/feat/login\n"
        ),
    ]);

    $this->artisan('forge-test-branches:list', ['--orphans' => true])
        ->expectsOutput('No orphaned environments found.')
        ->assertExitCode(0);
});

test('lista apenas ambientes órfãos com flag --orphans', function (): void {
    $builder = Mockery::mock(EnvironmentBuilder::class);
    $builder->shouldReceive('listAll')
        ->once()
        ->andReturn([
            makeListEnvData('feat/login', 'feat-login', 100),
            makeListEnvData('feat/removed', 'feat-removed', 200),
        ]);

    $this->app->instance(EnvironmentBuilder::class, $builder);

    Process::fake([
        'git ls-remote --heads *' => Process::result(
            output: "abc123\trefs/heads/feat/login\n"
        ),
    ]);

    $this->artisan('forge-test-branches:list', ['--orphans' => true])
        ->expectsTable(
            ['Branch', 'Domain', 'Status', 'Site ID'],
            [
                ['feat/removed', 'feat-removed.review.example.com', 'Orphan', 200],
            ]
        )
        ->assertExitCode(0);
});

test('destrói ambientes órfãos com confirmação', function (): void {
    $orphanEnvironment = makeListEnvData('feat/removed', 'feat-removed', 200);
    $fullEnvironment = new EnvironmentData(
        branch: 'feat/removed',
        slug: 'feat-removed',
        domain: 'feat-removed.review.example.com',
        serverId: 123,
        siteId: 200,
        databaseId: 10,
        databaseUserId: 20,
    );

    $builder = Mockery::mock(EnvironmentBuilder::class);
    $builder->shouldReceive('listAll')
        ->once()
        ->andReturn([
            makeListEnvData('feat/login', 'feat-login', 100),
            $orphanEnvironment,
        ]);
    $builder->shouldReceive('find')
        ->once()
        ->with('feat/removed')
        ->andReturn($fullEnvironment);
    $builder->shouldReceive('destroy')
        ->once()
        ->with($fullEnvironment);

    $this->app->instance(EnvironmentBuilder::class, $builder);

    Process::fake([
        'git ls-remote --heads *' => Process::result(
            output: "abc123\trefs/heads/feat/login\n"
        ),
    ]);

    $this->artisan('forge-test-branches:list', ['--destroy-orphans' => true])
        ->expectsConfirmation('Destroy 1 orphaned environment(s)? [feat/removed]', 'yes')
        ->expectsOutput('All orphaned environments destroyed.')
        ->assertExitCode(0);
});

test('cancela destruição de órfãos quando não confirma', function (): void {
    $builder = Mockery::mock(EnvironmentBuilder::class);
    $builder->shouldReceive('listAll')
        ->once()
        ->andReturn([
            makeListEnvData('feat/removed', 'feat-removed', 200),
        ]);

    $this->app->instance(EnvironmentBuilder::class, $builder);

    Process::fake([
        'git ls-remote --heads *' => Process::result(
            output: "abc123\trefs/heads/main\n"
        ),
    ]);

    $this->artisan('forge-test-branches:list', ['--destroy-orphans' => true])
        ->expectsConfirmation('Destroy 1 orphaned environment(s)? [feat/removed]', 'no')
        ->expectsOutput('Operation cancelled.')
        ->assertExitCode(0);
});

test('exibe warning e lista sem status quando remote falha', function (): void {
    $builder = Mockery::mock(EnvironmentBuilder::class);
    $builder->shouldReceive('listAll')
        ->once()
        ->andReturn([
            makeListEnvData('feat/login', 'feat-login', 100),
        ]);

    $this->app->instance(EnvironmentBuilder::class, $builder);

    Process::fake([
        'git ls-remote --heads *' => Process::result(exitCode: 128),
    ]);

    $this->artisan('forge-test-branches:list')
        ->expectsOutput('Could not fetch remote branches. Showing all environments without status.')
        ->expectsTable(
            ['Branch', 'Domain', 'Status', 'Site ID'],
            [
                ['feat/login', 'feat-login.review.example.com', 'Active', 100],
            ]
        )
        ->assertExitCode(0);
});

test('exibe erro quando filtra órfãos sem acesso ao remote', function (): void {
    $builder = Mockery::mock(EnvironmentBuilder::class);
    $builder->shouldReceive('listAll')
        ->once()
        ->andReturn([
            makeListEnvData('feat/login', 'feat-login', 100),
        ]);

    $this->app->instance(EnvironmentBuilder::class, $builder);

    Process::fake([
        'git ls-remote --heads *' => Process::result(exitCode: 128),
    ]);

    $this->artisan('forge-test-branches:list', ['--orphans' => true])
        ->expectsOutput('Cannot filter orphans without remote branch data.')
        ->assertExitCode(1);
});

test('exibe erro quando destruição de órfão falha', function (): void {
    $fullEnvironment = new EnvironmentData(
        branch: 'feat/removed',
        slug: 'feat-removed',
        domain: 'feat-removed.review.example.com',
        serverId: 123,
        siteId: 200,
        databaseId: 10,
        databaseUserId: 20,
    );

    $builder = Mockery::mock(EnvironmentBuilder::class);
    $builder->shouldReceive('listAll')
        ->once()
        ->andReturn([
            makeListEnvData('feat/removed', 'feat-removed', 200),
        ]);
    $builder->shouldReceive('find')
        ->once()
        ->with('feat/removed')
        ->andReturn($fullEnvironment);
    $builder->shouldReceive('destroy')
        ->once()
        ->andThrow(new RuntimeException('Forge API Error'));

    $this->app->instance(EnvironmentBuilder::class, $builder);

    Process::fake([
        'git ls-remote --heads *' => Process::result(
            output: "abc123\trefs/heads/main\n"
        ),
    ]);

    $this->artisan('forge-test-branches:list', ['--destroy-orphans' => true])
        ->expectsConfirmation('Destroy 1 orphaned environment(s)? [feat/removed]', 'yes')
        ->expectsOutput('  Error: Forge API Error')
        ->assertExitCode(1);
});

test('exibe warning quando ambiente órfão não é encontrado no find', function (): void {
    $builder = Mockery::mock(EnvironmentBuilder::class);
    $builder->shouldReceive('listAll')
        ->once()
        ->andReturn([
            makeListEnvData('feat/removed', 'feat-removed', 200),
        ]);
    $builder->shouldReceive('find')
        ->once()
        ->with('feat/removed')
        ->andReturnNull();

    $this->app->instance(EnvironmentBuilder::class, $builder);

    Process::fake([
        'git ls-remote --heads *' => Process::result(
            output: "abc123\trefs/heads/main\n"
        ),
    ]);

    $this->artisan('forge-test-branches:list', ['--destroy-orphans' => true])
        ->expectsConfirmation('Destroy 1 orphaned environment(s)? [feat/removed]', 'yes')
        ->expectsOutput('  Environment not found: feat/removed')
        ->assertExitCode(0);
});

test('destrói órfãos sem confirmação com flag --force', function (): void {
    $fullEnvironment = new EnvironmentData(
        branch: 'feat/removed',
        slug: 'feat-removed',
        domain: 'feat-removed.review.example.com',
        serverId: 123,
        siteId: 200,
        databaseId: 10,
        databaseUserId: 20,
    );

    $builder = Mockery::mock(EnvironmentBuilder::class);
    $builder->shouldReceive('listAll')
        ->once()
        ->andReturn([
            makeListEnvData('feat/removed', 'feat-removed', 200),
        ]);
    $builder->shouldReceive('find')
        ->once()
        ->with('feat/removed')
        ->andReturn($fullEnvironment);
    $builder->shouldReceive('destroy')
        ->once()
        ->with($fullEnvironment);

    $this->app->instance(EnvironmentBuilder::class, $builder);

    Process::fake([
        'git ls-remote --heads *' => Process::result(
            output: "abc123\trefs/heads/main\n"
        ),
    ]);

    $this->artisan('forge-test-branches:list', ['--destroy-orphans' => true, '--force' => true])
        ->expectsOutput('All orphaned environments destroyed.')
        ->assertExitCode(0);
});
