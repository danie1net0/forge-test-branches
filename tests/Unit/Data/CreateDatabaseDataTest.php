<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\CreateDatabaseData;

test('creates instance with name only', function (): void {
    $data = new CreateDatabaseData(name: 'review_db');

    expect($data->name)->toBe('review_db')
        ->and($data->user)->toBeNull()
        ->and($data->password)->toBeNull();
});

test('creates instance with all parameters', function (): void {
    $data = new CreateDatabaseData(
        name: 'review_db',
        user: 'review_user',
        password: 'secret123',
    );

    expect($data->name)->toBe('review_db')
        ->and($data->user)->toBe('review_user')
        ->and($data->password)->toBe('secret123');
});
