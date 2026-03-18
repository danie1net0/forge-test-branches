<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Services\RemoteBranchResolver;
use Illuminate\Support\Facades\Process;

beforeEach(function (): void {
    config([
        'forge-test-branches.git.provider' => 'gitlab',
        'forge-test-branches.git.repository' => 'user/repo',
    ]);
});

test('resolve branches remotas via git ls-remote', function (): void {
    Process::fake([
        'git ls-remote --heads *' => Process::result(
            output: "abc123\trefs/heads/main\ndef456\trefs/heads/feat/login\n"
        ),
    ]);

    $resolver = new RemoteBranchResolver();

    expect($resolver->resolve())->toBe(['main', 'feat/login']);
});

test('tenta SSH quando HTTPS falha', function (): void {
    Process::fake([
        'git ls-remote --heads https://*' => Process::result(exitCode: 128),
        'git ls-remote --heads git@*' => Process::result(
            output: "abc123\trefs/heads/main\n"
        ),
    ]);

    $resolver = new RemoteBranchResolver();

    expect($resolver->resolve())->toBe(['main']);
});

test('retorna null quando ambos protocolos falham', function (): void {
    Process::fake([
        'git ls-remote --heads *' => Process::result(exitCode: 128),
    ]);

    $resolver = new RemoteBranchResolver();

    expect($resolver->resolve())->toBeNull();
});

test('retorna null para provider não suportado', function (): void {
    config(['forge-test-branches.git.provider' => 'unsupported']);

    $resolver = new RemoteBranchResolver();

    expect($resolver->resolve())->toBeNull();
});

test('resolve host correto para cada provider', function (string $provider, string $expectedHost): void {
    config(['forge-test-branches.git.provider' => $provider]);

    Process::fake([
        "git ls-remote --heads https://{$expectedHost}/*" => Process::result(
            output: "abc123\trefs/heads/main\n"
        ),
    ]);

    $resolver = new RemoteBranchResolver();

    expect($resolver->resolve())->toBe(['main']);
})->with([
    ['github', 'github.com'],
    ['gitlab', 'gitlab.com'],
    ['bitbucket', 'bitbucket.org'],
]);

test('retorna array vazio quando repositório não tem branches', function (): void {
    Process::fake([
        'git ls-remote --heads *' => Process::result(output: ''),
    ]);

    $resolver = new RemoteBranchResolver();

    expect($resolver->resolve())->toBe([]);
});
