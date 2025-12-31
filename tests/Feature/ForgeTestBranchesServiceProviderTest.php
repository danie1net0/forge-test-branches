<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\ForgeTestBranches;
use Ddr\ForgeTestBranches\Integrations\Forge\ForgeClient;
use Ddr\ForgeTestBranches\Services\{BranchPatternMatcher, BranchSanitizer, DeploymentScriptBuilder, DomainBuilder, EnvironmentBuilder};
use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    config(['forge-test-branches.forge_api_token' => 'fake-token']);
});

test('registers ForgeClient as singleton', function (): void {
    $client1 = resolve(ForgeClient::class);
    $client2 = resolve(ForgeClient::class);

    expect($client1)->toBe($client2);
});

test('registers BranchSanitizer as singleton', function (): void {
    $service1 = resolve(BranchSanitizer::class);
    $service2 = resolve(BranchSanitizer::class);

    expect($service1)->toBe($service2);
});

test('registers BranchPatternMatcher as singleton', function (): void {
    $service1 = resolve(BranchPatternMatcher::class);
    $service2 = resolve(BranchPatternMatcher::class);

    expect($service1)->toBe($service2);
});

test('registers DomainBuilder as singleton', function (): void {
    $service1 = resolve(DomainBuilder::class);
    $service2 = resolve(DomainBuilder::class);

    expect($service1)->toBe($service2);
});

test('registers DeploymentScriptBuilder as singleton', function (): void {
    $service1 = resolve(DeploymentScriptBuilder::class);
    $service2 = resolve(DeploymentScriptBuilder::class);

    expect($service1)->toBe($service2);
});

test('registers EnvironmentBuilder as singleton', function (): void {
    $service1 = resolve(EnvironmentBuilder::class);
    $service2 = resolve(EnvironmentBuilder::class);

    expect($service1)->toBe($service2);
});

test('registers ForgeTestBranches as singleton', function (): void {
    $service1 = resolve(ForgeTestBranches::class);
    $service2 = resolve(ForgeTestBranches::class);

    expect($service1)->toBe($service2);
});

test('loads webhook routes when enabled', function (): void {
    config(['forge-test-branches.webhook.enabled' => true]);

    expect(Route::has('forge-test-branches.webhook'))->toBeTrue();
});
