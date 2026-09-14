<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\DatabaseUserData;
use Ddr\ForgeTestBranches\Integrations\Forge\ForgeConnector;
use Ddr\ForgeTestBranches\Integrations\Forge\Requests\Databases\GetDatabaseUserRequest;
use Saloon\Http\Faking\{MockClient, MockResponse};

test('resolve endpoint corretamente', function (): void {
    $request = new GetDatabaseUserRequest(123, 3);

    expect($request->resolveEndpoint())->toBe('/servers/123/database/users/3');
});

test('usa método GET', function (): void {
    $request = new GetDatabaseUserRequest(123, 3);

    expect($request->getMethod()->value)->toBe('GET');
});

test('retorna DatabaseUserData do response', function (): void {
    $mockClient = new MockClient([
        GetDatabaseUserRequest::class => MockResponse::make(forgeDocument(forgeResource('databaseUsers', 3, forgeDatabaseUserAttributes('user_one')))),
    ]);

    $connector = new ForgeConnector('test-token', 'test-org');
    $connector->withMockClient($mockClient);

    $request = new GetDatabaseUserRequest(123, 3);
    $response = $connector->send($request);

    expect($request->createDtoFromResponse($response))->toBeInstanceOf(DatabaseUserData::class)
        ->id->toBe(3)
        ->serverId->toBe(123)
        ->name->toBe('user_one')
        ->status->toBe('installed');
});
