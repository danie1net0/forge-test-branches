<?php

use Ddr\ForgeTestBranches\Services\DeploymentScriptBuilder;

test('generates default deploy script with branch', function (): void {
    config(['forge-test-branches.deploy.script' => null]);

    $builder = new DeploymentScriptBuilder();
    $script = $builder->build('feat/hu-123');

    expect($script)->toContain('git pull origin feat/hu-123')
        ->and($script)->toContain('$FORGE_COMPOSER install');
});

test('uses custom script when configured', function (): void {
    config(['forge-test-branches.deploy.script' => 'git pull origin {branch} && composer install']);

    $builder = new DeploymentScriptBuilder();
    $script = $builder->build('feat/hu-123');

    expect($script)->toBe('git pull origin feat/hu-123 && composer install');
});
