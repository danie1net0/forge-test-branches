<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\CreateSiteData;

test('creates instance with required parameters', function (): void {
    $data = new CreateSiteData(
        domain: 'test.example.com',
        projectType: 'php',
    );

    expect($data)
        ->domain->toBe('test.example.com')
        ->projectType->toBe('php')
        ->aliases->toBeNull()
        ->directory->toBeNull();
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

    expect($data)
        ->domain->toBe('test.example.com')
        ->projectType->toBe('php')
        ->aliases->toBe(['alias.example.com'])
        ->directory->toBe('/public')
        ->isolated->toBeTrue()
        ->username->toBe('testuser')
        ->database->toBe('testdb')
        ->phpVersion->toBe('php83')
        ->nginxTemplate->toBe(1);
});
