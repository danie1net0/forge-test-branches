<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\CreateSiteData;

test('creates instance with required parameters', function (): void {
    $data = new CreateSiteData(
        domain: 'test.example.com',
        projectType: 'php',
    );

    expect($data->domain)->toBe('test.example.com')
        ->and($data->projectType)->toBe('php')
        ->and($data->aliases)->toBeNull()
        ->and($data->directory)->toBeNull();
});

test('creates instance with all parameters', function (): void {
    $data = new CreateSiteData(
        domain: 'test.example.com',
        projectType: 'php',
        aliases: ['alias.example.com'],
        directory: '/public',
        isolated: true,
        username: 'testuser',
        database: 'testdb',
        phpVersion: 'php83',
        nginxTemplate: 1,
    );

    expect($data->domain)->toBe('test.example.com')
        ->and($data->projectType)->toBe('php')
        ->and($data->aliases)->toBe(['alias.example.com'])
        ->and($data->directory)->toBe('/public')
        ->and($data->isolated)->toBeTrue()
        ->and($data->username)->toBe('testuser')
        ->and($data->database)->toBe('testdb')
        ->and($data->phpVersion)->toBe('php83')
        ->and($data->nginxTemplate)->toBe(1);
});
