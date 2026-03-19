<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Services\DeploymentScriptBuilder;

test('gera script de deploy padrão com branch', function (): void {
    config([
        'forge-test-branches.deploy.script' => null,
        'forge-test-branches.deploy.seed' => false,
    ]);

    $builder = new DeploymentScriptBuilder();
    $script = $builder->build('feat/hu-123');

    expect($script)
        ->toContain('git fetch origin feat/hu-123')
        ->toContain('git reset --hard origin/feat/hu-123')
        ->toContain('git clean -fd')
        ->toContain('$FORGE_COMPOSER install')
        ->not->toContain('db:seed');
});

test('usa script customizado quando configurado', function (): void {
    config(['forge-test-branches.deploy.script' => 'git pull origin {branch} && composer install']);

    $builder = new DeploymentScriptBuilder();
    $script = $builder->build('feat/hu-123');

    expect($script)->toBe('git pull origin feat/hu-123 && composer install');
});

test('inclui comando seed quando seed está habilitado', function (): void {
    config([
        'forge-test-branches.deploy.script' => null,
        'forge-test-branches.deploy.seed' => true,
        'forge-test-branches.deploy.seed_class' => null,
    ]);

    $builder = new DeploymentScriptBuilder();
    $script = $builder->build('feat/hu-123');

    expect($script)->toContain('artisan db:seed --force');
});

test('inclui comando seed com classe específica quando configurado', function (): void {
    config([
        'forge-test-branches.deploy.script' => null,
        'forge-test-branches.deploy.seed' => true,
        'forge-test-branches.deploy.seed_class' => 'ReviewSeeder',
    ]);

    $builder = new DeploymentScriptBuilder();
    $script = $builder->build('feat/hu-123');

    expect($script)->toContain('artisan db:seed --class=ReviewSeeder --force');
});

test('cria e remove auth.json antes e depois do composer install', function (): void {
    config([
        'forge-test-branches.deploy.script' => null,
        'forge-test-branches.deploy.seed' => false,
    ]);

    $builder = new DeploymentScriptBuilder();
    $script = $builder->build('feat/hu-123');

    expect($script)
        ->toContain('artisan forge-test-branches:create-auth-json')
        ->toContain('$FORGE_COMPOSER install')
        ->toContain('artisan forge-test-branches:create-auth-json --cleanup');
});

test('lança exceção para nome de branch com caracteres inválidos', function (): void {
    config(['forge-test-branches.deploy.script' => null]);

    $builder = new DeploymentScriptBuilder();
    $builder->build('feat/test; rm -rf /');
})->throws(InvalidArgumentException::class, 'Branch name contains invalid characters');

test('lança exceção para nome de classe de seed inválido', function (): void {
    config([
        'forge-test-branches.deploy.script' => null,
        'forge-test-branches.deploy.seed' => true,
        'forge-test-branches.deploy.seed_class' => 'Invalid Class!',
    ]);

    $builder = new DeploymentScriptBuilder();
    $builder->build('feat/test');
})->throws(InvalidArgumentException::class, 'Invalid seed class name');
