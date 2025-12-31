<?php

declare(strict_types=1);

beforeEach(function (): void {
    config(['forge-test-branches.forge_api_token' => 'fake-token']);
});

test('allows request when secret is not configured', function (): void {
    config(['forge-test-branches.webhook.secret' => null]);

    $this->postJson('/forge-test-branches/webhook', [], ['X-Gitlab-Event' => 'Push Hook'])
        ->assertOk();
});

test('allows request when token matches secret', function (): void {
    config(['forge-test-branches.webhook.secret' => 'my-secret-token']);

    $this->postJson('/forge-test-branches/webhook', [], [
        'X-Gitlab-Event' => 'Push Hook',
        'X-Gitlab-Token' => 'my-secret-token',
    ])
        ->assertOk();
});

test('rejects request when token does not match secret', function (): void {
    config(['forge-test-branches.webhook.secret' => 'my-secret-token']);

    $this->postJson('/forge-test-branches/webhook', [], [
        'X-Gitlab-Event' => 'Push Hook',
        'X-Gitlab-Token' => 'wrong-token',
    ])
        ->assertUnauthorized();
});

test('rejects request when token is missing and secret is configured', function (): void {
    config(['forge-test-branches.webhook.secret' => 'my-secret-token']);

    $this->postJson('/forge-test-branches/webhook', [], ['X-Gitlab-Event' => 'Push Hook'])
        ->assertUnauthorized();
});

test('allows github request when signature is valid', function (): void {
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

test('rejects github request when signature is invalid', function (): void {
    config(['forge-test-branches.webhook.secret' => 'my-secret-token']);

    $payload = json_encode(['ref' => 'feat/test', 'ref_type' => 'branch']);

    $this->call('POST', '/forge-test-branches/webhook', [], [], [], [
        'HTTP_X-GitHub-Event' => 'delete',
        'HTTP_X-Hub-Signature-256' => 'sha256=invalid-signature',
        'CONTENT_TYPE' => 'application/json',
    ], $payload)
        ->assertUnauthorized();
});

test('rejects github request when signature is missing', function (): void {
    config(['forge-test-branches.webhook.secret' => 'my-secret-token']);

    $this->postJson('/forge-test-branches/webhook', ['ref' => 'feat/test'], [
        'X-GitHub-Event' => 'delete',
    ])
        ->assertUnauthorized();
});
