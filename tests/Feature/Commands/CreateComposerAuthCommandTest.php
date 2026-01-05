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

test('cria auth.json com configuração simples', function (): void {
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

test('processa placeholders {env:VAR} corretamente', function (): void {
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

test('suporta múltiplos providers', function (): void {
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

test('não cria arquivo quando configuração está vazia', function (): void {
    config(['forge-test-branches.composer_auth' => []]);

    $this->artisan(CreateComposerAuthCommand::class)
        ->expectsOutput('No composer_auth configuration found. Skipping auth.json creation.')
        ->assertExitCode(0);

    expect(file_exists(base_path('auth.json')))->toBeFalse();
});

test('remove auth.json com opção --cleanup', function (): void {
    $authPath = base_path('auth.json');
    file_put_contents($authPath, '{"test": "value"}');

    expect(file_exists($authPath))->toBeTrue();

    $this->artisan(CreateComposerAuthCommand::class, ['--cleanup' => true])
        ->expectsOutput('Successfully removed auth.json')
        ->assertExitCode(0);

    expect(file_exists($authPath))->toBeFalse();
});

test('cleanup não falha quando auth.json não existe', function (): void {
    expect(file_exists(base_path('auth.json')))->toBeFalse();

    $this->artisan(CreateComposerAuthCommand::class, ['--cleanup' => true])
        ->assertExitCode(0);
});

test('gera JSON válido e formatado', function (): void {
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
