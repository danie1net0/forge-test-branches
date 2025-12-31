<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\EnvironmentData;
use Ddr\ForgeTestBranches\Services\EnvironmentBuilder;

beforeEach(function (): void {
    config(['forge-test-branches.forge_api_token' => 'fake-token']);
    config(['forge-test-branches.webhook.secret' => null]);
});

function makeWebhookEnvData(string $branch, string $slug): EnvironmentData
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

test('ignora eventos que não são push hook', function (): void {
    $this->postJson('/forge-test-branches/webhook', [], ['X-Gitlab-Event' => 'Merge Request Hook'])
        ->assertOk()
        ->assertJson(['message' => 'Event ignored']);
});

test('ignora quando não é uma exclusão de branch', function (): void {
    $this->postJson('/forge-test-branches/webhook', [
        'ref' => 'refs/heads/feat/test',
        'after' => 'abc123',
    ], ['X-Gitlab-Event' => 'Push Hook'])
        ->assertOk()
        ->assertJson(['message' => 'Not a branch deletion']);
});

test('retorna ambiente não encontrado quando branch não existe', function (): void {
    $builder = Mockery::mock(EnvironmentBuilder::class);
    $builder->shouldReceive('find')
        ->once()
        ->with('feat/nonexistent')
        ->andReturnNull();

    $this->app->instance(EnvironmentBuilder::class, $builder);

    $this->postJson('/forge-test-branches/webhook', [
        'ref' => 'refs/heads/feat/nonexistent',
        'after' => '0000000000000000000000000000000000000000',
    ], ['X-Gitlab-Event' => 'Push Hook'])
        ->assertOk()
        ->assertJson(['message' => 'Environment not found']);
});

test('destrói ambiente com sucesso ao receber webhook de exclusão', function (): void {
    $environment = makeWebhookEnvData('feat/to-destroy', 'feat-to-destroy');

    $builder = Mockery::mock(EnvironmentBuilder::class);
    $builder->shouldReceive('find')
        ->once()
        ->with('feat/to-destroy')
        ->andReturn($environment);
    $builder->shouldReceive('destroy')
        ->once()
        ->with($environment);

    $this->app->instance(EnvironmentBuilder::class, $builder);

    $this->postJson('/forge-test-branches/webhook', [
        'ref' => 'refs/heads/feat/to-destroy',
        'after' => '0000000000000000000000000000000000000000',
    ], ['X-Gitlab-Event' => 'Push Hook'])
        ->assertOk()
        ->assertJson(['message' => 'Environment destroyed successfully']);
});

test('retorna erro quando destruição do ambiente falha', function (): void {
    $environment = makeWebhookEnvData('feat/error', 'feat-error');

    $builder = Mockery::mock(EnvironmentBuilder::class);
    $builder->shouldReceive('find')
        ->once()
        ->with('feat/error')
        ->andReturn($environment);
    $builder->shouldReceive('destroy')
        ->once()
        ->andThrow(new RuntimeException('API Error'));

    $this->app->instance(EnvironmentBuilder::class, $builder);

    $this->postJson('/forge-test-branches/webhook', [
        'ref' => 'refs/heads/feat/error',
        'after' => '0000000000000000000000000000000000000000',
    ], ['X-Gitlab-Event' => 'Push Hook'])
        ->assertStatus(500)
        ->assertJson(['message' => 'Error destroying environment', 'error' => 'API Error']);
});

test('ignora eventos do github que não são delete', function (): void {
    $this->postJson('/forge-test-branches/webhook', [], ['X-GitHub-Event' => 'push'])
        ->assertOk()
        ->assertJson(['message' => 'Event ignored']);
});

test('ignora delete do github quando ref_type não é branch', function (): void {
    $this->postJson('/forge-test-branches/webhook', [
        'ref' => 'v1.0.0',
        'ref_type' => 'tag',
    ], ['X-GitHub-Event' => 'delete'])
        ->assertOk()
        ->assertJson(['message' => 'Not a branch deletion']);
});

test('retorna ambiente não encontrado para branch inexistente via github', function (): void {
    $builder = Mockery::mock(EnvironmentBuilder::class);
    $builder->shouldReceive('find')
        ->once()
        ->with('feat/nonexistent')
        ->andReturnNull();

    $this->app->instance(EnvironmentBuilder::class, $builder);

    $this->postJson('/forge-test-branches/webhook', [
        'ref' => 'feat/nonexistent',
        'ref_type' => 'branch',
    ], ['X-GitHub-Event' => 'delete'])
        ->assertOk()
        ->assertJson(['message' => 'Environment not found']);
});

test('destrói ambiente via webhook do github', function (): void {
    $environment = makeWebhookEnvData('feat/github-destroy', 'feat-github-destroy');

    $builder = Mockery::mock(EnvironmentBuilder::class);
    $builder->shouldReceive('find')
        ->once()
        ->with('feat/github-destroy')
        ->andReturn($environment);
    $builder->shouldReceive('destroy')
        ->once()
        ->with($environment);

    $this->app->instance(EnvironmentBuilder::class, $builder);

    $this->postJson('/forge-test-branches/webhook', [
        'ref' => 'feat/github-destroy',
        'ref_type' => 'branch',
    ], ['X-GitHub-Event' => 'delete'])
        ->assertOk()
        ->assertJson(['message' => 'Environment destroyed successfully']);
});
