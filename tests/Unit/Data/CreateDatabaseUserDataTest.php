<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\CreateDatabaseUserData;

test('creates instance with required parameters', function (): void {
    $data = new CreateDatabaseUserData(
        name: 'review_user',
        password: 'secret123',
    );

    expect($data)
        ->name->toBe('review_user')
        ->password->toBe('secret123')
        ->databases->toBe([]);
});

test('creates instance with databases', function (): void {
    $data = new CreateDatabaseUserData(
        name: 'review_user',
        password: 'secret123',
        databases: [789, 790],
    );

    expect($data)
        ->name->toBe('review_user')
        ->password->toBe('secret123')
        ->databases->toBe([789, 790]);
});

test('serializes to array with correct field names for Forge API', function (): void {
    $data = new CreateDatabaseUserData(
        name: 'review_user',
        password: 'secret123',
        databases: [789],
    );

    $array = $data->toArray();

    expect($array)
        ->toHaveKey('name')
        ->toHaveKey('password')
        ->toHaveKey('databases')
        ->and($array['name'])->toBe('review_user')
        ->and($array['password'])->toBe('secret123');
});
