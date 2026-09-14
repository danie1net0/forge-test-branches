<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\DatabaseUserData;

test('creates instance with all parameters', function (): void {
    $data = new DatabaseUserData(
        id: 101,
        serverId: 123,
        name: 'review_user',
        status: 'installed',
        createdAt: '2025-07-29T09:00:00Z',
    );

    expect($data)
        ->id->toBe(101)
        ->serverId->toBe(123)
        ->name->toBe('review_user')
        ->getName()->toBe('review_user')
        ->status->toBe('installed')
        ->isInstalled()->toBeTrue();
});

test('indica se o usuário do banco está instalado', function (string $status, bool $expected): void {
    $data = new DatabaseUserData(
        id: 101,
        serverId: 123,
        name: 'review_user',
        status: $status,
        createdAt: '2025-07-29T09:00:00Z',
    );

    expect($data->isInstalled())->toBe($expected);
})->with([
    'instalado' => ['installed', true],
    'instalando' => ['installing', false],
    'atualizando' => ['updating', false],
    'removendo' => ['removing', false],
]);
