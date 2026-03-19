<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\DatabaseData;
use Ddr\ForgeTestBranches\Integrations\Forge\ForgeConnector;
use Ddr\ForgeTestBranches\Integrations\Forge\Requests\Databases\ListDatabasesRequest;
use Saloon\Http\Faking\{MockClient, MockResponse};

test('resolve endpoint corretamente', function (): void {
    $request = new ListDatabasesRequest(123);

    expect($request->resolveEndpoint())->toBe('/servers/123/databases');
});

test('usa método GET', function (): void {
    $request = new ListDatabasesRequest(123);

    expect($request->getMethod()->value)->toBe('GET');
});

test('lista databases e retorna array de DTOs', function (): void {
    $mockClient = new MockClient([
        ListDatabasesRequest::class => MockResponse::make([
            'databases' => [
                [
                    'id' => 1,
                    'name' => 'db_one',
                    'status' => 'installed',
                    'created_at' => '2024-01-01 00:00:00',
                ],
                [
                    'id' => 2,
                    'name' => 'db_two',
                    'status' => 'installed',
                    'created_at' => '2024-01-02 00:00:00',
                ],
            ],
        ]),
    ]);

    $connector = new ForgeConnector('test-token');
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
        ListDatabasesRequest::class => MockResponse::make([
            'databases' => null,
        ]),
    ]);

    $connector = new ForgeConnector('test-token');
    $connector->withMockClient($mockClient);

    $request = new ListDatabasesRequest(123);
    $response = $connector->send($request);
    $result = $request->createDtoFromResponse($response);

    expect($result)->toBeEmpty();
});
