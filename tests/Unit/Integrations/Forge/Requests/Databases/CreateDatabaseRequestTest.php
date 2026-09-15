<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\{CreateDatabaseData, DatabaseData};
use Ddr\ForgeTestBranches\Integrations\Forge\ForgeConnector;
use Ddr\ForgeTestBranches\Integrations\Forge\Requests\Databases\CreateDatabaseRequest;
use Saloon\Http\Faking\{MockClient, MockResponse};

test('resolve endpoint corretamente', function (): void {
    $request = new CreateDatabaseRequest(123, new CreateDatabaseData(name: 'test_db'));

    expect($request->resolveEndpoint())->toBe('/servers/123/database/schemas');
});

test('usa método POST', function (): void {
    $request = new CreateDatabaseRequest(123, new CreateDatabaseData(name: 'test_db'));

    expect($request->getMethod()->value)->toBe('POST');
});

test('envia nome do database no corpo', function (): void {
    $request = new CreateDatabaseRequest(123, new CreateDatabaseData(name: 'test_db'));

    expect($request->body()->all())->toBe(['name' => 'test_db']);
});

test('cria database e retorna DTO correto', function (): void {
    $mockClient = new MockClient([
        CreateDatabaseRequest::class => MockResponse::make(forgeDocument(forgeResource('databases', 1, [
            'name' => 'test_db',
            'status' => 'installing',
            'created_at' => '2025-07-29T09:00:00Z',
            'updated_at' => '2025-07-29T09:00:00Z',
        ])), 202),
    ]);

    $connector = new ForgeConnector('test-token', 'test-org');
    $connector->withMockClient($mockClient);

    $request = new CreateDatabaseRequest(123, new CreateDatabaseData(name: 'test_db'));
    $response = $connector->send($request);
    $result = $request->createDtoFromResponse($response);

    expect($result)->toBeInstanceOf(DatabaseData::class)
        ->id->toBe(1)
        ->serverId->toBe(123)
        ->name->toBe('test_db')
        ->status->toBe('installing');
});
