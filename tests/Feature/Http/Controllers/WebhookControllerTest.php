<?php

declare(strict_types=1);

use Illuminate\Testing\TestResponse;
use Ddr\ForgeTestBranches\Data\EnvironmentData;
use Ddr\ForgeTestBranches\Services\EnvironmentBuilder;

beforeEach(function (): void {
    config(['forge-test-branches.forge_api_token' => 'fake-token']);
    config(['forge-test-branches.webhook.secret' => 'test-secret']);
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

/** @param array<string, mixed> $payload */
function postGitLabWebhook(object $testCase, array $payload = [], string $event = 'Push Hook'): TestResponse
{
    return $testCase->postJson('/forge-test-branches/webhook', $payload, [
        'X-Gitlab-Event' => $event,
        'X-Gitlab-Token' => 'test-secret',
    ]);
}

/** @param array<string, mixed> $payload */
function postGitHubWebhook(object $testCase, array $payload = [], string $event = 'delete'): TestResponse
{
    $jsonPayload = (string) json_encode($payload);
    $signature = 'sha256=' . hash_hmac('sha256', $jsonPayload, 'test-secret');

    return $testCase->call('POST', '/forge-test-branches/webhook', [], [], [], [
        'HTTP_X-GitHub-Event' => $event,
        'HTTP_X-Hub-Signature-256' => $signature,
        'CONTENT_TYPE' => 'application/json',
    ], $jsonPayload);
}

test('ignora eventos que não são push hook', function (): void {
    postGitLabWebhook($this, [], 'Merge Request Hook')
        ->assertOk()
        ->assertJson(['message' => 'Event ignored']);
});

test('ignora quando não é deleção de branch', function (): void {
    postGitLabWebhook($this, [
        'ref' => 'refs/heads/feat/test',
        'after' => 'abc123',
    ])
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

    postGitLabWebhook($this, [
        'ref' => 'refs/heads/feat/nonexistent',
        'after' => '0000000000000000000000000000000000000000',
    ])
        ->assertOk()
        ->assertJson(['message' => 'Environment not found']);
});

test('destrói ambiente ao receber webhook de deleção', function (): void {
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

    postGitLabWebhook($this, [
        'ref' => 'refs/heads/feat/to-destroy',
        'after' => '0000000000000000000000000000000000000000',
    ])
        ->assertOk()
        ->assertJson(['message' => 'Environment destroyed successfully']);
});

test('retorna erro genérico quando destruição falha', function (): void {
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

    postGitLabWebhook($this, [
        'ref' => 'refs/heads/feat/error',
        'after' => '0000000000000000000000000000000000000000',
    ])
        ->assertStatus(500)
        ->assertJson(['message' => 'Error destroying environment'])
        ->assertJsonMissing(['error']);
});

test('ignora eventos GitHub que não são delete', function (): void {
    postGitHubWebhook($this, [], 'push')
        ->assertOk()
        ->assertJson(['message' => 'Event ignored']);
});

test('ignora delete do GitHub quando ref_type não é branch', function (): void {
    postGitHubWebhook($this, [
        'ref' => 'v1.0.0',
        'ref_type' => 'tag',
    ])
        ->assertOk()
        ->assertJson(['message' => 'Not a branch deletion']);
});

test('retorna ambiente não encontrado para branch inexistente via GitHub', function (): void {
    $builder = Mockery::mock(EnvironmentBuilder::class);
    $builder->shouldReceive('find')
        ->once()
        ->with('feat/nonexistent')
        ->andReturnNull();

    $this->app->instance(EnvironmentBuilder::class, $builder);

    postGitHubWebhook($this, [
        'ref' => 'feat/nonexistent',
        'ref_type' => 'branch',
    ])
        ->assertOk()
        ->assertJson(['message' => 'Environment not found']);
});

test('destrói ambiente via webhook do GitHub', function (): void {
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

    postGitHubWebhook($this, [
        'ref' => 'feat/github-destroy',
        'ref_type' => 'branch',
    ])
        ->assertOk()
        ->assertJson(['message' => 'Environment destroyed successfully']);
});
