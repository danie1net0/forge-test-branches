<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\CreateSiteData;

test('cria instância com parâmetros obrigatórios', function (): void {
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

test('cria instância com todos os parâmetros', function (): void {
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

test('filtra valores null no toArray', function (): void {
    $data = new CreateSiteData(
        domain: 'test.example.com',
        projectType: 'php',
        directory: '/public',
    );

    expect($data->toArray())->toHaveKeys(['domain', 'project_type', 'directory'])
        ->not->toHaveKeys(['aliases', 'isolated', 'username', 'database', 'php_version', 'nginx_template']);
});

test('mantém todos os valores não-null no toArray', function (): void {
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

    expect($data->toArray())
        ->toHaveKey('domain', 'test.example.com')
        ->toHaveKey('project_type', 'php')
        ->toHaveKey('directory', '/public')
        ->toHaveKey('isolated', true)
        ->toHaveKey('username', 'testuser');
});
