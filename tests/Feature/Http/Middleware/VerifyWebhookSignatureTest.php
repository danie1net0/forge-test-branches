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
