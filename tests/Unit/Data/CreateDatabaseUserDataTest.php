<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\CreateDatabaseUserData;

test('creates instance with required parameters', function (): void {
    $data = new CreateDatabaseUserData(
        name: 'review_user',
        password: 'secret123',
    );

    expect($data->name)->toBe('review_user')
        ->and($data->password)->toBe('secret123')
        ->and($data->databases)->toBe([]);
});

test('creates instance with databases', function (): void {
    $data = new CreateDatabaseUserData(
        name: 'review_user',
        password: 'secret123',
        databases: [789, 790],
    );

    expect($data->name)->toBe('review_user')
        ->and($data->password)->toBe('secret123')
        ->and($data->databases)->toBe([789, 790]);
});
