<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\{CreateDatabaseUserData, DatabaseUserData};
use Ddr\ForgeTestBranches\Integrations\Forge\ForgeConnector;
use Ddr\ForgeTestBranches\Integrations\Forge\Requests\Databases\CreateDatabaseUserRequest;
use Saloon\Http\Faking\{MockClient, MockResponse};

test('resolve endpoint corretamente', function (): void {
    $data = new CreateDatabaseUserData(name: 'test_user', password: 'secret123', databases: [1]);
    $request = new CreateDatabaseUserRequest(123, $data);

    expect($request->resolveEndpoint())->toBe('/servers/123/database-users');
});

test('usa método POST', function (): void {
    $data = new CreateDatabaseUserData(name: 'test_user', password: 'secret123', databases: [1]);
    $request = new CreateDatabaseUserRequest(123, $data);

    expect($request->getMethod()->value)->toBe('POST');
});

test('cria usuário de database e retorna DTO correto', function (): void {
    $mockClient = new MockClient([
        CreateDatabaseUserRequest::class => MockResponse::make([
            'user' => [
                'id' => 2,
                'name' => 'test_user',
                'status' => 'installed',
                'created_at' => '2024-01-01 00:00:00',
                'databases' => [1],
            ],
        ]),
    ]);

    $connector = new ForgeConnector('test-token');
    $connector->withMockClient($mockClient);

    $data = new CreateDatabaseUserData(name: 'test_user', password: 'secret123', databases: [1]);
    $request = new CreateDatabaseUserRequest(123, $data);
    $response = $connector->send($request);
    $result = $request->createDtoFromResponse($response);

    expect($result)->toBeInstanceOf(DatabaseUserData::class)
        ->id->toBe(2)
        ->serverId->toBe(123)
        ->name->toBe('test_user')
        ->status->toBe('installed')
        ->databases->toBe([1]);
});
