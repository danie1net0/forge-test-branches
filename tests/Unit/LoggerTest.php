<?php

declare(strict_types=1);

use Psr\Log\LoggerInterface;
use Ddr\ForgeTestBranches\Logger;
use Illuminate\Support\Facades\Log;

test('não loga info quando logging está desabilitado', function (): void {
    config([
        'forge-test-branches.logging.enabled' => false,
        'forge-test-branches.logging.channel' => 'forge-test-branches',
    ]);

    $channel = Mockery::mock(LoggerInterface::class);
    $channel->shouldNotReceive('info');

    Log::shouldReceive('channel')
        ->with('forge-test-branches')
        ->andReturn($channel);

    $logger = new Logger();
    $logger->info('test message');
});

test('não loga debug quando logging está desabilitado', function (): void {
    config([
        'forge-test-branches.logging.enabled' => false,
        'forge-test-branches.logging.channel' => 'forge-test-branches',
    ]);

    $channel = Mockery::mock(LoggerInterface::class);
    $channel->shouldNotReceive('debug');

    Log::shouldReceive('channel')
        ->with('forge-test-branches')
        ->andReturn($channel);

    $logger = new Logger();
    $logger->debug('test message');
});

test('loga info quando logging está habilitado', function (): void {
    config([
        'forge-test-branches.logging.enabled' => true,
        'forge-test-branches.logging.channel' => 'forge-test-branches',
    ]);

    $channel = Mockery::mock(LoggerInterface::class);
    $channel->shouldReceive('info')
        ->once()
        ->with('test message', ['key' => 'value']);

    Log::shouldReceive('channel')
        ->with('forge-test-branches')
        ->andReturn($channel);

    $logger = new Logger();
    $logger->info('test message', ['key' => 'value']);
});

test('loga debug quando logging está habilitado', function (): void {
    config([
        'forge-test-branches.logging.enabled' => true,
        'forge-test-branches.logging.channel' => 'forge-test-branches',
    ]);

    $channel = Mockery::mock(LoggerInterface::class);
    $channel->shouldReceive('debug')
        ->once()
        ->with('test message', []);

    Log::shouldReceive('channel')
        ->with('forge-test-branches')
        ->andReturn($channel);

    $logger = new Logger();
    $logger->debug('test message');
});

test('loga error sempre independente de enabled', function (): void {
    config([
        'forge-test-branches.logging.enabled' => false,
        'forge-test-branches.logging.channel' => 'forge-test-branches',
    ]);

    $channel = Mockery::mock(LoggerInterface::class);
    $channel->shouldReceive('error')
        ->once()
        ->with('error message', ['error' => 'details']);

    Log::shouldReceive('channel')
        ->with('forge-test-branches')
        ->andReturn($channel);

    $logger = new Logger();
    $logger->error('error message', ['error' => 'details']);
});

test('loga warning sempre independente de enabled', function (): void {
    config([
        'forge-test-branches.logging.enabled' => false,
        'forge-test-branches.logging.channel' => 'forge-test-branches',
    ]);

    $channel = Mockery::mock(LoggerInterface::class);
    $channel->shouldReceive('warning')
        ->once()
        ->with('warning message', []);

    Log::shouldReceive('channel')
        ->with('forge-test-branches')
        ->andReturn($channel);

    $logger = new Logger();
    $logger->warning('warning message');
});
