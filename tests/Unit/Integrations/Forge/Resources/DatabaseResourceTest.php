<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\{CreateDatabaseData, DatabaseData};
use Ddr\ForgeTestBranches\Integrations\Forge\ForgeConnector;
use Ddr\ForgeTestBranches\Integrations\Forge\Requests\Databases\{CreateDatabaseRequest, DeleteDatabaseRequest, ListDatabasesRequest};
use Ddr\ForgeTestBranches\Integrations\Forge\Resources\DatabaseResource;
use Saloon\Http\Faking\{MockClient, MockResponse};

test('lista databases do servidor', function (): void {
    $mockClient = new MockClient([
        ListDatabasesRequest::class => MockResponse::make([
            'databases' => [
                ['id' => 1, 'name' => 'db_one', 'status' => 'installed', 'created_at' => '2024-01-01 00:00:00'],
                ['id' => 2, 'name' => 'db_two', 'status' => 'installed', 'created_at' => '2024-01-02 00:00:00'],
            ],
        ]),
    ]);

    $connector = new ForgeConnector('test-token');
    $connector->withMockClient($mockClient);

    $resource = new DatabaseResource($connector);
    $result = $resource->list(123);

    expect($result)->toHaveCount(2)
        ->and($result[0])->toBeInstanceOf(DatabaseData::class)
        ->name->toBe('db_one')
        ->and($result[1])->toBeInstanceOf(DatabaseData::class)
        ->name->toBe('db_two');
});

test('encontra database pelo nome', function (): void {
    $mockClient = new MockClient([
        ListDatabasesRequest::class => MockResponse::make([
            'databases' => [
                ['id' => 1, 'name' => 'db_one', 'status' => 'installed', 'created_at' => '2024-01-01 00:00:00'],
                ['id' => 2, 'name' => 'db_two', 'status' => 'installed', 'created_at' => '2024-01-02 00:00:00'],
            ],
        ]),
    ]);

    $connector = new ForgeConnector('test-token');
    $connector->withMockClient($mockClient);

    $resource = new DatabaseResource($connector);
    $result = $resource->findByName(123, 'db_two');

    expect($result)->toBeInstanceOf(DatabaseData::class)
        ->id->toBe(2)
        ->name->toBe('db_two');
});

test('retorna null quando database não é encontrada pelo nome', function (): void {
    $mockClient = new MockClient([
        ListDatabasesRequest::class => MockResponse::make([
            'databases' => [
                ['id' => 1, 'name' => 'db_one', 'status' => 'installed', 'created_at' => '2024-01-01 00:00:00'],
            ],
        ]),
    ]);

    $connector = new ForgeConnector('test-token');
    $connector->withMockClient($mockClient);

    $resource = new DatabaseResource($connector);

    expect($resource->findByName(123, 'nonexistent'))->toBeNull();
});

test('cria database e retorna DTO', function (): void {
    $mockClient = new MockClient([
        CreateDatabaseRequest::class => MockResponse::make([
            'database' => [
                'id' => 5,
                'name' => 'new_db',
                'status' => 'installing',
                'created_at' => '2024-01-01 00:00:00',
            ],
        ]),
    ]);

    $connector = new ForgeConnector('test-token');
    $connector->withMockClient($mockClient);

    $resource = new DatabaseResource($connector);
    $result = $resource->create(123, new CreateDatabaseData(name: 'new_db'));

    expect($result)->toBeInstanceOf(DatabaseData::class)
        ->id->toBe(5)
        ->name->toBe('new_db')
        ->status->toBe('installing');
});

test('deleta database com sucesso', function (): void {
    $mockClient = new MockClient([
        DeleteDatabaseRequest::class => MockResponse::make([]),
    ]);

    $connector = new ForgeConnector('test-token');
    $connector->withMockClient($mockClient);

    $resource = new DatabaseResource($connector);
    $resource->delete(123, 789);

    $mockClient->assertSent(DeleteDatabaseRequest::class);
});
