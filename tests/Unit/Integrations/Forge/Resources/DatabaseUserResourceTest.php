<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\{CreateDatabaseUserData, DatabaseUserData};
use Ddr\ForgeTestBranches\Integrations\Forge\ForgeConnector;
use Ddr\ForgeTestBranches\Integrations\Forge\Requests\Databases\{CreateDatabaseUserRequest, DeleteDatabaseUserRequest, ListDatabaseUsersRequest};
use Ddr\ForgeTestBranches\Integrations\Forge\Resources\DatabaseUserResource;
use Saloon\Http\Faking\{MockClient, MockResponse};

test('lista usuários de database do servidor', function (): void {
    $mockClient = new MockClient([
        ListDatabaseUsersRequest::class => MockResponse::make([
            'users' => [
                ['id' => 1, 'name' => 'user_one', 'status' => 'installed', 'created_at' => '2024-01-01 00:00:00', 'databases' => [1]],
                ['id' => 2, 'name' => 'user_two', 'status' => 'installed', 'created_at' => '2024-01-02 00:00:00', 'databases' => [2]],
            ],
        ]),
    ]);

    $connector = new ForgeConnector('test-token');
    $connector->withMockClient($mockClient);

    $resource = new DatabaseUserResource($connector);
    $result = $resource->list(123);

    expect($result)->toHaveCount(2)
        ->and($result[0])->toBeInstanceOf(DatabaseUserData::class)
        ->name->toBe('user_one')
        ->and($result[1])->toBeInstanceOf(DatabaseUserData::class)
        ->name->toBe('user_two');
});

test('encontra usuário de database pelo nome', function (): void {
    $mockClient = new MockClient([
        ListDatabaseUsersRequest::class => MockResponse::make([
            'users' => [
                ['id' => 1, 'name' => 'user_one', 'status' => 'installed', 'created_at' => '2024-01-01 00:00:00', 'databases' => [1]],
                ['id' => 2, 'name' => 'user_two', 'status' => 'installed', 'created_at' => '2024-01-02 00:00:00', 'databases' => [2]],
            ],
        ]),
    ]);

    $connector = new ForgeConnector('test-token');
    $connector->withMockClient($mockClient);

    $resource = new DatabaseUserResource($connector);
    $result = $resource->findByName(123, 'user_two');

    expect($result)->toBeInstanceOf(DatabaseUserData::class)
        ->id->toBe(2)
        ->name->toBe('user_two');
});

test('retorna null quando usuário de database não é encontrado', function (): void {
    $mockClient = new MockClient([
        ListDatabaseUsersRequest::class => MockResponse::make([
            'users' => [
                ['id' => 1, 'name' => 'user_one', 'status' => 'installed', 'created_at' => '2024-01-01 00:00:00', 'databases' => [1]],
            ],
        ]),
    ]);

    $connector = new ForgeConnector('test-token');
    $connector->withMockClient($mockClient);

    $resource = new DatabaseUserResource($connector);

    expect($resource->findByName(123, 'nonexistent'))->toBeNull();
});

test('cria usuário de database e retorna DTO', function (): void {
    $mockClient = new MockClient([
        CreateDatabaseUserRequest::class => MockResponse::make([
            'user' => [
                'id' => 5,
                'name' => 'new_user',
                'status' => 'installing',
                'created_at' => '2024-01-01 00:00:00',
                'databases' => [1],
            ],
        ]),
    ]);

    $connector = new ForgeConnector('test-token');
    $connector->withMockClient($mockClient);

    $resource = new DatabaseUserResource($connector);
    $result = $resource->create(123, new CreateDatabaseUserData(name: 'new_user', password: 'secret', databases: [1]));

    expect($result)->toBeInstanceOf(DatabaseUserData::class)
        ->id->toBe(5)
        ->name->toBe('new_user')
        ->status->toBe('installing');
});

test('deleta usuário de database com sucesso', function (): void {
    $mockClient = new MockClient([
        DeleteDatabaseUserRequest::class => MockResponse::make([]),
    ]);

    $connector = new ForgeConnector('test-token');
    $connector->withMockClient($mockClient);

    $resource = new DatabaseUserResource($connector);
    $resource->delete(123, 101);

    $mockClient->assertSent(DeleteDatabaseUserRequest::class);
});
