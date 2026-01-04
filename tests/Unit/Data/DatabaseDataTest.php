<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\DatabaseData;

test('creates instance with all parameters', function (): void {
    $data = new DatabaseData(
        id: 789,
        serverId: 123,
        name: 'review_db',
        status: 'installed',
        createdAt: '2024-01-01 00:00:00',
    );

    expect($data)
        ->id->toBe(789)
        ->serverId->toBe(123)
        ->name->toBe('review_db')
        ->status->toBe('installed')
        ->createdAt->toBe('2024-01-01 00:00:00');
});
