<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Models\ReviewEnvironment;

beforeEach(function (): void {
    config(['forge-test-branches.forge_api_token' => 'fake-token']);
});

test('creates environment with all fields', function (): void {
    $environment = ReviewEnvironment::query()->create([
        'branch' => 'feat/test',
        'slug' => 'feat-test',
        'domain' => 'feat-test.review.example.com',
        'server_id' => 123,
        'site_id' => 456,
        'database_id' => 789,
        'database_user_id' => 101,
    ]);

    expect($environment->branch)->toBe('feat/test')
        ->and($environment->slug)->toBe('feat-test')
        ->and($environment->domain)->toBe('feat-test.review.example.com')
        ->and($environment->server_id)->toBe(123)
        ->and($environment->site_id)->toBe(456)
        ->and($environment->database_id)->toBe(789)
        ->and($environment->database_user_id)->toBe(101);
});

test('casts convert ids to integer', function (): void {
    $environment = ReviewEnvironment::query()->create([
        'branch' => 'feat/test',
        'slug' => 'feat-test',
        'domain' => 'feat-test.review.example.com',
        'server_id' => '123',
        'site_id' => '456',
        'database_id' => '789',
        'database_user_id' => '101',
    ]);

    expect($environment->server_id)->toBeInt()
        ->and($environment->site_id)->toBeInt()
        ->and($environment->database_id)->toBeInt()
        ->and($environment->database_user_id)->toBeInt();
});

test('finds environment by branch', function (): void {
    ReviewEnvironment::query()->create([
        'branch' => 'feat/findme',
        'slug' => 'feat-findme',
        'domain' => 'feat-findme.review.example.com',
        'server_id' => 123,
        'site_id' => 456,
        'database_id' => 789,
        'database_user_id' => 101,
    ]);

    $found = ReviewEnvironment::query()->where('branch', 'feat/findme')->first();

    expect($found)->not->toBeNull()
        ->and($found->branch)->toBe('feat/findme');
});

test('deletes environment', function (): void {
    $environment = ReviewEnvironment::query()->create([
        'branch' => 'feat/delete',
        'slug' => 'feat-delete',
        'domain' => 'feat-delete.review.example.com',
        'server_id' => 123,
        'site_id' => 456,
        'database_id' => 789,
        'database_user_id' => 101,
    ]);

    $environment->delete();

    expect(ReviewEnvironment::query()->where('branch', 'feat/delete')->exists())->toBeFalse();
});
