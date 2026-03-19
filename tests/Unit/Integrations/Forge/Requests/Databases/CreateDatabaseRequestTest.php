<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\{CreateDatabaseData, DatabaseData};
use Ddr\ForgeTestBranches\Integrations\Forge\ForgeConnector;
use Ddr\ForgeTestBranches\Integrations\Forge\Requests\Databases\CreateDatabaseRequest;
use Saloon\Http\Faking\{MockClient, MockResponse};

test('resolve endpoint corretamente', function (): void {
    $data = new CreateDatabaseData(name: 'test_db');
    $request = new CreateDatabaseRequest(123, $data);

    expect($request->resolveEndpoint())->toBe('/servers/123/databases');
});

test('usa método POST', function (): void {
    $data = new CreateDatabaseData(name: 'test_db');
    $request = new CreateDatabaseRequest(123, $data);

    expect($request->getMethod()->value)->toBe('POST');
});

test('cria database e retorna DTO correto', function (): void {
    $mockClient = new MockClient([
        CreateDatabaseRequest::class => MockResponse::make([
            'database' => [
                'id' => 1,
                'name' => 'test_db',
                'status' => 'installed',
                'created_at' => '2024-01-01 00:00:00',
            ],
        ]),
    ]);

    $connector = new ForgeConnector('test-token');
    $connector->withMockClient($mockClient);

    $data = new CreateDatabaseData(name: 'test_db');
    $request = new CreateDatabaseRequest(123, $data);
    $response = $connector->send($request);
    $result = $request->createDtoFromResponse($response);

    expect($result)->toBeInstanceOf(DatabaseData::class)
        ->id->toBe(1)
        ->serverId->toBe(123)
        ->name->toBe('test_db')
        ->status->toBe('installed');
});
