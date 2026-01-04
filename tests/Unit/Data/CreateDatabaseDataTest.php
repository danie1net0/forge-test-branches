<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\CreateDatabaseData;

test('creates instance with name', function (): void {
    $data = new CreateDatabaseData(name: 'review_db');

    expect($data->name)->toBe('review_db');
});

test('serializes to array with correct field names for Forge API', function (): void {
    $data = new CreateDatabaseData(name: 'review_db');

    $array = $data->toArray();

    expect($array)
        ->toHaveKey('name')
        ->not->toHaveKey('user')
        ->not->toHaveKey('password')
        ->and($array['name'])->toBe('review_db');
});
