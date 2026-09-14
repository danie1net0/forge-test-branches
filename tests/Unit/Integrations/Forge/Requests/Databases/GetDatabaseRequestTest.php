<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\DatabaseData;
use Ddr\ForgeTestBranches\Integrations\Forge\ForgeConnector;
use Ddr\ForgeTestBranches\Integrations\Forge\Requests\Databases\GetDatabaseRequest;
use Saloon\Http\Faking\{MockClient, MockResponse};

test('resolve endpoint corretamente', function (): void {
    $request = new GetDatabaseRequest(123, 7);

    expect($request->resolveEndpoint())->toBe('/servers/123/database/schemas/7');
});

test('usa método GET', function (): void {
    $request = new GetDatabaseRequest(123, 7);

    expect($request->getMethod()->value)->toBe('GET');
});

test('retorna DatabaseData do response', function (): void {
    $mockClient = new MockClient([
        GetDatabaseRequest::class => MockResponse::make(forgeDocument(forgeResource('databases', 7, forgeDatabaseAttributes('db_one')))),
    ]);

    $connector = new ForgeConnector('test-token', 'test-org');
    $connector->withMockClient($mockClient);

    $request = new GetDatabaseRequest(123, 7);
    $response = $connector->send($request);

    expect($request->createDtoFromResponse($response))->toBeInstanceOf(DatabaseData::class)
        ->id->toBe(7)
        ->serverId->toBe(123)
        ->name->toBe('db_one')
        ->status->toBe('installed');
});
