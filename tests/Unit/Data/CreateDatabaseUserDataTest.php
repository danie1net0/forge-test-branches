<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\CreateDatabaseUserData;

test('creates instance with required parameters', function (): void {
    $data = new CreateDatabaseUserData(
        user: 'review_user',
        password: 'secret123',
    );

    expect($data->user)->toBe('review_user')
        ->and($data->password)->toBe('secret123')
        ->and($data->databases)->toBe([]);
});

test('creates instance with databases', function (): void {
    $data = new CreateDatabaseUserData(
        user: 'review_user',
        password: 'secret123',
        databases: [789, 790],
    );

    expect($data->user)->toBe('review_user')
        ->and($data->password)->toBe('secret123')
        ->and($data->databases)->toBe([789, 790]);
});

test('serializes to array with correct field names for Forge API', function (): void {
    $data = new CreateDatabaseUserData(
        user: 'review_user',
        password: 'secret123',
        databases: [789],
    );

    $array = $data->toArray();

    expect($array)->toHaveKey('user')
        ->and($array['user'])->toBe('review_user')
        ->and($array)->toHaveKey('password')
        ->and($array['password'])->toBe('secret123')
        ->and($array)->toHaveKey('databases')
        ->and($array)->not->toHaveKey('name');
});
