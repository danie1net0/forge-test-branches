<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Models\ReviewEnvironment;
use Ddr\ForgeTestBranches\Services\EnvironmentBuilder;

beforeEach(function (): void {
    config(['forge-test-branches.forge_api_token' => 'fake-token']);
    config(['forge-test-branches.webhook.secret' => null]);
});

test('ignores events that are not push hook', function (): void {
    $this->postJson('/forge-test-branches/webhook', [], ['X-Gitlab-Event' => 'Merge Request Hook'])
        ->assertOk()
        ->assertJson(['message' => 'Event ignored']);
});

test('ignores when it is not a branch deletion', function (): void {
    $this->postJson('/forge-test-branches/webhook', [
        'ref' => 'refs/heads/feat/test',
        'after' => 'abc123',
    ], ['X-Gitlab-Event' => 'Push Hook'])
        ->assertOk()
        ->assertJson(['message' => 'Not a branch deletion']);
});

test('returns environment not found when branch does not exist', function (): void {
    $this->postJson('/forge-test-branches/webhook', [
        'ref' => 'refs/heads/feat/nonexistent',
        'after' => '0000000000000000000000000000000000000000',
    ], ['X-Gitlab-Event' => 'Push Hook'])
        ->assertOk()
        ->assertJson(['message' => 'Environment not found']);
});

test('destroys environment successfully when receiving deletion webhook', function (): void {
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

    $this->postJson('/forge-test-branches/webhook', [
        'ref' => 'refs/heads/feat/to-destroy',
        'after' => '0000000000000000000000000000000000000000',
    ], ['X-Gitlab-Event' => 'Push Hook'])
        ->assertOk()
        ->assertJson(['message' => 'Environment destroyed successfully']);
});

test('returns error when environment destruction fails', function (): void {
    $environment = ReviewEnvironment::query()->create([
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

    $this->postJson('/forge-test-branches/webhook', [
        'ref' => 'refs/heads/feat/error',
        'after' => '0000000000000000000000000000000000000000',
    ], ['X-Gitlab-Event' => 'Push Hook'])
        ->assertStatus(500)
        ->assertJson(['message' => 'Error destroying environment', 'error' => 'API Error']);
});
