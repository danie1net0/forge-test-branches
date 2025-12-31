<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Services\EnvironmentBuilder;

beforeEach(function (): void {
    config(['forge-test-branches.forge_api_token' => 'fake-token']);

    $builder = Mockery::mock(EnvironmentBuilder::class);
    $builder->shouldReceive('find')->andReturnNull();
    $this->app->instance(EnvironmentBuilder::class, $builder);
});

test('permite requisição quando secret não está configurado', function (): void {
    config(['forge-test-branches.webhook.secret' => null]);

    $this->postJson('/forge-test-branches/webhook', [], ['X-Gitlab-Event' => 'Push Hook'])
        ->assertOk();
});

test('permite requisição quando token corresponde ao secret', function (): void {
    config(['forge-test-branches.webhook.secret' => 'my-secret-token']);

    $this->postJson('/forge-test-branches/webhook', [], [
        'X-Gitlab-Event' => 'Push Hook',
        'X-Gitlab-Token' => 'my-secret-token',
    ])
        ->assertOk();
});

test('rejeita requisição quando token não corresponde ao secret', function (): void {
    config(['forge-test-branches.webhook.secret' => 'my-secret-token']);

    $this->postJson('/forge-test-branches/webhook', [], [
        'X-Gitlab-Event' => 'Push Hook',
        'X-Gitlab-Token' => 'wrong-token',
    ])
        ->assertUnauthorized();
});

test('rejeita requisição quando token está ausente e secret está configurado', function (): void {
    config(['forge-test-branches.webhook.secret' => 'my-secret-token']);

    $this->postJson('/forge-test-branches/webhook', [], ['X-Gitlab-Event' => 'Push Hook'])
        ->assertUnauthorized();
});

test('permite requisição do github quando assinatura é válida', function (): void {
    $secret = 'my-secret-token';
    config(['forge-test-branches.webhook.secret' => $secret]);

    $payload = json_encode(['ref' => 'feat/test', 'ref_type' => 'branch']);
    $signature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

    $this->call('POST', '/forge-test-branches/webhook', [], [], [], [
        'HTTP_X-GitHub-Event' => 'delete',
        'HTTP_X-Hub-Signature-256' => $signature,
        'CONTENT_TYPE' => 'application/json',
    ], $payload)
        ->assertOk();
});

test('rejeita requisição do github quando assinatura é inválida', function (): void {
    config(['forge-test-branches.webhook.secret' => 'my-secret-token']);

    $payload = json_encode(['ref' => 'feat/test', 'ref_type' => 'branch']);

    $this->call('POST', '/forge-test-branches/webhook', [], [], [], [
        'HTTP_X-GitHub-Event' => 'delete',
        'HTTP_X-Hub-Signature-256' => 'sha256=invalid-signature',
        'CONTENT_TYPE' => 'application/json',
    ], $payload)
        ->assertUnauthorized();
});

test('rejeita requisição do github quando assinatura está ausente', function (): void {
    config(['forge-test-branches.webhook.secret' => 'my-secret-token']);

    $this->postJson('/forge-test-branches/webhook', ['ref' => 'feat/test'], [
        'X-GitHub-Event' => 'delete',
    ])
        ->assertUnauthorized();
});
