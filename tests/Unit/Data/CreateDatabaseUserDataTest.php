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
        ->databaseIds->toBe([]);
});

test('creates instance with database ids', function (): void {
    $data = new CreateDatabaseUserData(
        name: 'review_user',
        password: 'secret123',
        databaseIds: [789, 790],
    );

    expect($data)
        ->name->toBe('review_user')
        ->password->toBe('secret123')
        ->databaseIds->toBe([789, 790]);
});

test('serializes to array with correct field names for Forge API', function (): void {
    $data = new CreateDatabaseUserData(
        name: 'review_user',
        password: 'secret123',
        databaseIds: [789],
    );

    expect($data->toArray())->toBe([
        'name' => 'review_user',
        'password' => 'secret123',
        'database_ids' => [789],
    ]);
});
