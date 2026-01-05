<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Commands\CreateComposerAuthCommand;

beforeEach(function (): void {
    $authPath = base_path('auth.json');

    if (file_exists($authPath)) {
        unlink($authPath);
    }
});

afterEach(function (): void {
    $authPath = base_path('auth.json');

    if (file_exists($authPath)) {
        unlink($authPath);
    }
});

test('creates auth.json with simple configuration', function (): void {
    config([
        'forge-test-branches.composer_auth' => [
            'gitlab-token' => [
                'gitlab.com' => 'test-token-123',
            ],
        ],
    ]);

    $this->artisan(CreateComposerAuthCommand::class)
        ->expectsOutput('Successfully created auth.json')
        ->assertExitCode(0);

    $authPath = base_path('auth.json');
    expect(file_exists($authPath))->toBeTrue();

    $content = json_decode((string) file_get_contents($authPath), true);
    expect($content)
        ->toHaveKey('gitlab-token')
        ->and($content['gitlab-token'])
        ->toHaveKey('gitlab.com')
        ->and($content['gitlab-token']['gitlab.com'])->toBe('test-token-123');
});

test('processes {env:VAR} placeholders correctly', function (): void {
    putenv('TEST_GITLAB_TOKEN=my-secret-token');

    config([
        'forge-test-branches.composer_auth' => [
            'gitlab-token' => [
                'gitlab.com' => '{env:TEST_GITLAB_TOKEN}',
            ],
        ],
    ]);

    $this->artisan(CreateComposerAuthCommand::class)
        ->assertExitCode(0);

    $authPath = base_path('auth.json');
    $content = json_decode((string) file_get_contents($authPath), true);

    expect($content['gitlab-token']['gitlab.com'])->toBe('my-secret-token');

    putenv('TEST_GITLAB_TOKEN');
});

test('supports multiple providers', function (): void {
    config([
        'forge-test-branches.composer_auth' => [
            'gitlab-token' => [
                'gitlab.com' => 'gitlab-token-123',
            ],
            'github-oauth' => [
                'github.com' => 'github-token-456',
            ],
        ],
    ]);

    $this->artisan(CreateComposerAuthCommand::class)
        ->assertExitCode(0);

    $authPath = base_path('auth.json');
    $content = json_decode((string) file_get_contents($authPath), true);

    expect($content)
        ->toHaveKey('gitlab-token')
        ->toHaveKey('github-oauth')
        ->and($content['gitlab-token']['gitlab.com'])->toBe('gitlab-token-123')
        ->and($content['github-oauth']['github.com'])->toBe('github-token-456');
});

test('does not create file when configuration is empty', function (): void {
    config(['forge-test-branches.composer_auth' => []]);

    $this->artisan(CreateComposerAuthCommand::class)
        ->expectsOutput('No composer_auth configuration found. Skipping auth.json creation.')
        ->assertExitCode(0);

    expect(file_exists(base_path('auth.json')))->toBeFalse();
});

test('removes auth.json with --cleanup option', function (): void {
    $authPath = base_path('auth.json');
    file_put_contents($authPath, '{"test": "value"}');

    expect(file_exists($authPath))->toBeTrue();

    $this->artisan(CreateComposerAuthCommand::class, ['--cleanup' => true])
        ->expectsOutput('Successfully removed auth.json')
        ->assertExitCode(0);

    expect(file_exists($authPath))->toBeFalse();
});

test('cleanup does not fail when auth.json does not exist', function (): void {
    expect(file_exists(base_path('auth.json')))->toBeFalse();

    $this->artisan(CreateComposerAuthCommand::class, ['--cleanup' => true])
        ->assertExitCode(0);
});

test('generates valid and formatted JSON', function (): void {
    config([
        'forge-test-branches.composer_auth' => [
            'gitlab-token' => [
                'gitlab.com' => 'token123',
            ],
        ],
    ]);

    $this->artisan(CreateComposerAuthCommand::class)
        ->assertExitCode(0);

    $authPath = base_path('auth.json');
    $content = (string) file_get_contents($authPath);

    expect(json_validate($content))->toBeTrue()
        ->and($content)->toContain('{')
        ->toContain('}')
        ->toContain('gitlab-token');
});
