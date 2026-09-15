<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\{CreateDatabaseUserData, DatabaseUserData};
use Ddr\ForgeTestBranches\Integrations\Forge\ForgeConnector;
use Ddr\ForgeTestBranches\Integrations\Forge\Requests\Databases\CreateDatabaseUserRequest;
use Saloon\Http\Faking\{MockClient, MockResponse};

test('resolve endpoint corretamente', function (): void {
    $data = new CreateDatabaseUserData(name: 'test_user', password: 'secret123', databaseIds: [1]);
    $request = new CreateDatabaseUserRequest(123, $data);

    expect($request->resolveEndpoint())->toBe('/servers/123/database/users');
});

test('usa método POST', function (): void {
    $data = new CreateDatabaseUserData(name: 'test_user', password: 'secret123', databaseIds: [1]);
    $request = new CreateDatabaseUserRequest(123, $data);

    expect($request->getMethod()->value)->toBe('POST');
});

test('envia database_ids no corpo', function (): void {
    $data = new CreateDatabaseUserData(name: 'test_user', password: 'secret123', databaseIds: [1]);
    $request = new CreateDatabaseUserRequest(123, $data);

    expect($request->body()->all())->toBe([
        'name' => 'test_user',
        'password' => 'secret123',
        'database_ids' => [1],
    ]);
});

test('cria usuário de database e retorna DTO correto', function (): void {
    $mockClient = new MockClient([
        CreateDatabaseUserRequest::class => MockResponse::make(forgeDocument(forgeResource('databaseUsers', 2, [
            'name' => 'test_user',
            'status' => 'installing',
            'created_at' => '2025-07-29T09:00:00Z',
            'updated_at' => '2025-07-29T09:00:00Z',
        ])), 202),
    ]);

    $connector = new ForgeConnector('test-token', 'test-org');
    $connector->withMockClient($mockClient);

    $data = new CreateDatabaseUserData(name: 'test_user', password: 'secret123', databaseIds: [1]);
    $request = new CreateDatabaseUserRequest(123, $data);
    $response = $connector->send($request);
    $result = $request->createDtoFromResponse($response);

    expect($result)->toBeInstanceOf(DatabaseUserData::class)
        ->id->toBe(2)
        ->serverId->toBe(123)
        ->name->toBe('test_user')
        ->status->toBe('installing');
});
