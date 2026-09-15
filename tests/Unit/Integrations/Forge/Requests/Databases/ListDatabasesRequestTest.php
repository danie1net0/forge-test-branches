<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\DatabaseData;
use Ddr\ForgeTestBranches\Integrations\Forge\ForgeConnector;
use Ddr\ForgeTestBranches\Integrations\Forge\Requests\Databases\ListDatabasesRequest;
use Saloon\Http\Faking\{MockClient, MockResponse};

test('resolve endpoint corretamente', function (): void {
    $request = new ListDatabasesRequest(123);

    expect($request->resolveEndpoint())->toBe('/servers/123/database/schemas');
});

test('usa método GET', function (): void {
    $request = new ListDatabasesRequest(123);

    expect($request->getMethod()->value)->toBe('GET');
});

test('filtra pelo nome', function (): void {
    $request = new ListDatabasesRequest(123)->filterByName('db_one');

    expect($request->query()->get('filter[name]'))->toBe('db_one');
});

test('lista databases e retorna array de DTOs', function (): void {
    $mockClient = new MockClient([
        ListDatabasesRequest::class => MockResponse::make(forgeCollection([
            forgeResource('databases', 1, ['name' => 'db_one', 'status' => 'installed', 'created_at' => '2025-07-29T09:00:00Z']),
            forgeResource('databases', 2, ['name' => 'db_two', 'status' => 'installed', 'created_at' => '2025-07-30T09:00:00Z']),
        ])),
    ]);

    $connector = new ForgeConnector('test-token', 'test-org');
    $connector->withMockClient($mockClient);

    $request = new ListDatabasesRequest(123);
    $response = $connector->send($request);
    $result = $request->createDtoFromResponse($response);

    expect($result)->toHaveCount(2)
        ->and($result[0])->toBeInstanceOf(DatabaseData::class)
        ->id->toBe(1)
        ->serverId->toBe(123)
        ->name->toBe('db_one')
        ->and($result[1])->toBeInstanceOf(DatabaseData::class)
        ->id->toBe(2)
        ->name->toBe('db_two');
});

test('retorna array vazio quando não há databases', function (): void {
    $mockClient = new MockClient([
        ListDatabasesRequest::class => MockResponse::make(forgeCollection([])),
    ]);

    $connector = new ForgeConnector('test-token', 'test-org');
    $connector->withMockClient($mockClient);

    $request = new ListDatabasesRequest(123);
    $response = $connector->send($request);

    expect($request->createDtoFromResponse($response))->toBeEmpty();
});
