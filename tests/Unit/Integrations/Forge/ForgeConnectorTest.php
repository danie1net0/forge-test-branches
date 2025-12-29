<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Integrations\Forge\ForgeConnector;

test('resolves correct base url', function (): void {
    $connector = new ForgeConnector('test-token');

    expect($connector->resolveBaseUrl())->toBe('https://forge.laravel.com/api/v1');
});

test('includes default headers', function (): void {
    $connector = new ForgeConnector('test-token');
    $headers = $connector->headers()->all();

    expect($headers)->toHaveKey('Accept', 'application/json')
        ->toHaveKey('Content-Type', 'application/json');
});
