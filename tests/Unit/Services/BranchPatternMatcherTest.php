<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Services\BranchPatternMatcher;

test('allows all branches with wildcard pattern', function (): void {
    config(['forge-test-branches.branch.patterns' => ['*']]);

    $matcher = new BranchPatternMatcher();

    expect($matcher->isAllowed('feat/hu-123'))->toBeTrue();
    expect($matcher->isAllowed('main'))->toBeTrue();
    expect($matcher->isAllowed('any-branch'))->toBeTrue();
});

test('allows branches matching specific patterns', function (): void {
    config(['forge-test-branches.branch.patterns' => ['feat/*', 'fix/*']]);

    $matcher = new BranchPatternMatcher();

    expect($matcher->isAllowed('feat/hu-123'))->toBeTrue();
    expect($matcher->isAllowed('fix/bug-456'))->toBeTrue();
    expect($matcher->isAllowed('main'))->toBeFalse();
    expect($matcher->isAllowed('release/v1.0'))->toBeFalse();
});

test('allows branches matching review pattern', function (): void {
    config(['forge-test-branches.branch.patterns' => ['review/*']]);

    $matcher = new BranchPatternMatcher();

    expect($matcher->isAllowed('review/hu-123'))->toBeTrue();
    expect($matcher->isAllowed('feat/hu-123'))->toBeFalse();
});

test('matches exact branch names', function (): void {
    config(['forge-test-branches.branch.patterns' => ['develop', 'staging']]);

    $matcher = new BranchPatternMatcher();

    expect($matcher->isAllowed('develop'))->toBeTrue();
    expect($matcher->isAllowed('staging'))->toBeTrue();
    expect($matcher->isAllowed('main'))->toBeFalse();
});

test('matches with complex patterns', function (): void {
    config(['forge-test-branches.branch.patterns' => ['feat/hu-*', 'release/v*']]);

    $matcher = new BranchPatternMatcher();

    expect($matcher->isAllowed('feat/hu-123'))->toBeTrue();
    expect($matcher->isAllowed('feat/hu-456-test'))->toBeTrue();
    expect($matcher->isAllowed('feat/other'))->toBeFalse();
    expect($matcher->isAllowed('release/v1.0.0'))->toBeTrue();
});

test('returns false when no patterns match', function (): void {
    config(['forge-test-branches.branch.patterns' => ['feat/*']]);

    $matcher = new BranchPatternMatcher();

    expect($matcher->isAllowed('main'))->toBeFalse();
    expect($matcher->isAllowed('develop'))->toBeFalse();
});

test('uses default wildcard pattern when config is empty', function (): void {
    config(['forge-test-branches.branch.patterns' => null]);

    $matcher = new BranchPatternMatcher();

    expect($matcher->isAllowed('any-branch'))->toBeTrue();
});
