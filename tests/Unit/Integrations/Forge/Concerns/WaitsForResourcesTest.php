<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Exceptions\{ResourceFailedException, ResourceTimeoutException};
use Ddr\ForgeTestBranches\Integrations\Forge\Concerns\WaitsForResources;
use Illuminate\Support\Sleep;

function waiter(): object
{
    return new class() {
        use WaitsForResources;

        public function wait(
            Closure $fetchResource,
            Closure $isReady,
            int $maxAttempts,
            int $sleepSeconds,
            string $resourceLabel,
            ?Closure $hasFailed = null,
            ?Closure $describe = null,
        ): mixed {
            return $this->waitUntil($fetchResource, $isReady, $maxAttempts, $sleepSeconds, $resourceLabel, $hasFailed, $describe);
        }
    };
}

test('retorna assim que o recurso está pronto, sem dormir', function (): void {
    Sleep::fake();

    $result = waiter()->wait(
        fetchResource: fn (): string => 'ready',
        isReady: fn (string $resource): bool => $resource === 'ready',
        maxAttempts: 5,
        sleepSeconds: 2,
        resourceLabel: 'recurso',
    );

    expect($result)->toBe('ready');
    Sleep::assertNeverSlept();
});

test('tenta novamente até ficar pronto, dormindo entre as tentativas', function (): void {
    Sleep::fake();

    $attempts = 0;
    $states = ['pending', 'pending', 'ready'];

    $result = waiter()->wait(
        fetchResource: function () use (&$attempts, $states): string {
            return $states[$attempts++];
        },
        isReady: fn (string $resource): bool => $resource === 'ready',
        maxAttempts: 5,
        sleepSeconds: 3,
        resourceLabel: 'recurso',
    );

    expect($result)->toBe('ready')
        ->and($attempts)->toBe(3);

    Sleep::assertSequence([
        Sleep::for(3)->seconds(),
        Sleep::for(3)->seconds(),
    ]);
});

test('lança timeout após esgotar as tentativas, sem dormir depois da última', function (): void {
    Sleep::fake();

    $attempts = 0;

    $action = function () use (&$attempts): mixed {
        return waiter()->wait(
            fetchResource: function () use (&$attempts): string {
                $attempts++;

                return 'pending';
            },
            isReady: fn (string $resource): bool => false,
            maxAttempts: 3,
            sleepSeconds: 2,
            resourceLabel: 'meu recurso',
            describe: fn (string $resource): string => "state={$resource}",
        );
    };

    expect($action)->toThrow(ResourceTimeoutException::class, 'Timeout waiting for meu recurso after 3 attempts (last known state: state=pending).')
        ->and($attempts)->toBe(3);

    Sleep::assertSequence([
        Sleep::for(2)->seconds(),
        Sleep::for(2)->seconds(),
    ]);
});

test('lança exceção de falha imediatamente, sem esperar o timeout', function (): void {
    Sleep::fake();

    $attempts = 0;

    $action = function () use (&$attempts): mixed {
        return waiter()->wait(
            fetchResource: function () use (&$attempts): string {
                $attempts++;

                return 'failed';
            },
            isReady: fn (string $resource): bool => false,
            maxAttempts: 10,
            sleepSeconds: 5,
            resourceLabel: 'meu recurso',
            hasFailed: fn (string $resource): bool => $resource === 'failed',
            describe: fn (string $resource): string => "state={$resource}",
        );
    };

    expect($action)->toThrow(ResourceFailedException::class, 'meu recurso failed: state=failed')
        ->and($attempts)->toBe(1);

    Sleep::assertNeverSlept();
});

test('usa "unknown" quando nenhum describe é informado', function (): void {
    Sleep::fake();

    $action = fn () => waiter()->wait(
        fetchResource: fn (): string => 'pending',
        isReady: fn (string $resource): bool => false,
        maxAttempts: 1,
        sleepSeconds: 1,
        resourceLabel: 'recurso',
    );

    expect($action)->toThrow(ResourceTimeoutException::class, 'last known state: unknown)');
});
