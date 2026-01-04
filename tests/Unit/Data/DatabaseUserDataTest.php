<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\DatabaseUserData;

test('creates instance with all parameters', function (): void {
    $data = new DatabaseUserData(
        id: 101,
        serverId: 123,
        name: 'review_user',
        status: 'installed',
        createdAt: '2024-01-01 00:00:00',
        databases: [789, 790],
    );

    expect($data)
        ->id->toBe(101)
        ->serverId->toBe(123)
        ->name->toBe('review_user')
        ->status->toBe('installed')
        ->databases->toBe([789, 790]);
});

test('creates instance without databases', function (): void {
    $data = new DatabaseUserData(
        id: 101,
        serverId: 123,
        name: 'review_user',
        status: 'installed',
        createdAt: '2024-01-01 00:00:00',
    );

    expect($data->databases)->toBe([]);
});
