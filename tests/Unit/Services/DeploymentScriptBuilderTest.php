<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Services\DeploymentScriptBuilder;

test('generates default deploy script with branch', function (): void {
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

test('uses custom script when configured', function (): void {
    config(['forge-test-branches.deploy.script' => 'git pull origin {branch} && composer install']);

    $builder = new DeploymentScriptBuilder();
    $script = $builder->build('feat/hu-123');

    expect($script)->toBe('git pull origin feat/hu-123 && composer install');
});

test('includes seed command when seed is enabled', function (): void {
    config([
        'forge-test-branches.deploy.script' => null,
        'forge-test-branches.deploy.seed' => true,
        'forge-test-branches.deploy.seed_class' => null,
    ]);

    $builder = new DeploymentScriptBuilder();
    $script = $builder->build('feat/hu-123');

    expect($script)->toContain('artisan db:seed --force');
});

test('includes seed command with specific class when configured', function (): void {
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
