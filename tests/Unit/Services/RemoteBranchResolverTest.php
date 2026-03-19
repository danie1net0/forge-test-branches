<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Services\RemoteBranchResolver;
use Illuminate\Process\{FakeProcessResult, PendingProcess};
use Illuminate\Support\Facades\Process;

beforeEach(function (): void {
    config([
        'forge-test-branches.git.provider' => 'gitlab',
        'forge-test-branches.git.repository' => 'user/repo',
    ]);
});

test('resolve branches remotas via git ls-remote', function (): void {
    Process::fake([
        '*' => new FakeProcessResult(output: "abc123\trefs/heads/main\ndef456\trefs/heads/feat/login\n"),
    ]);

    $resolver = new RemoteBranchResolver();

    expect($resolver->resolve())->toBe(['main', 'feat/login']);
});

test('tenta SSH quando HTTPS falha', function (): void {
    $calls = 0;

    Process::fake(function () use (&$calls): FakeProcessResult {
        $calls++;

        if ($calls === 1) {
            return new FakeProcessResult(exitCode: 128);
        }

        return new FakeProcessResult(output: "abc123\trefs/heads/main\n");
    });

    $resolver = new RemoteBranchResolver();

    expect($resolver->resolve())->toBe(['main']);
});

test('retorna null quando ambos protocolos falham', function (): void {
    Process::fake([
        '*' => new FakeProcessResult(exitCode: 128),
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
        '*' => new FakeProcessResult(output: "abc123\trefs/heads/main\n"),
    ]);

    $resolver = new RemoteBranchResolver();

    expect($resolver->resolve())->toBe(['main']);

    Process::assertRan(fn (PendingProcess $process): bool => str_contains($process->command, $expectedHost));
})->with([
    ['github', 'github.com'],
    ['gitlab', 'gitlab.com'],
    ['bitbucket', 'bitbucket.org'],
]);

test('retorna array vazio quando repositório não tem branches', function (): void {
    Process::fake([
        '*' => new FakeProcessResult(output: ''),
    ]);

    $resolver = new RemoteBranchResolver();

    expect($resolver->resolve())->toBe([]);
});
