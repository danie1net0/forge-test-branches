<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\DatabaseUserData;
use Ddr\ForgeTestBranches\Integrations\Forge\ForgeConnector;
use Ddr\ForgeTestBranches\Integrations\Forge\Requests\Databases\ListDatabaseUsersRequest;
use Saloon\Http\Faking\{MockClient, MockResponse};

test('resolve endpoint corretamente', function (): void {
    $request = new ListDatabaseUsersRequest(123);

    expect($request->resolveEndpoint())->toBe('/servers/123/database/users');
});

test('usa método GET', function (): void {
    $request = new ListDatabaseUsersRequest(123);

    expect($request->getMethod()->value)->toBe('GET');
});

test('filtra pelo nome', function (): void {
    $request = new ListDatabaseUsersRequest(123)->filterByName('user_one');

    expect($request->query()->get('filter[name]'))->toBe('user_one');
});

test('lista usuários de database e retorna array de DTOs', function (): void {
    $mockClient = new MockClient([
        ListDatabaseUsersRequest::class => MockResponse::make(forgeCollection([
            forgeResource('databaseUsers', 1, ['name' => 'user_one', 'status' => 'installed', 'created_at' => '2025-07-29T09:00:00Z']),
            forgeResource('databaseUsers', 2, ['name' => 'user_two', 'status' => 'installed', 'created_at' => '2025-07-30T09:00:00Z']),
        ])),
    ]);

    $connector = new ForgeConnector('test-token', 'test-org');
    $connector->withMockClient($mockClient);

    $request = new ListDatabaseUsersRequest(123);
    $response = $connector->send($request);
    $result = $request->createDtoFromResponse($response);

    expect($result)->toHaveCount(2)
        ->and($result[0])->toBeInstanceOf(DatabaseUserData::class)
        ->id->toBe(1)
        ->serverId->toBe(123)
        ->name->toBe('user_one')
        ->and($result[1])->toBeInstanceOf(DatabaseUserData::class)
        ->id->toBe(2)
        ->name->toBe('user_two');
});

test('retorna array vazio quando não há usuários', function (): void {
    $mockClient = new MockClient([
        ListDatabaseUsersRequest::class => MockResponse::make(forgeCollection([])),
    ]);

    $connector = new ForgeConnector('test-token', 'test-org');
    $connector->withMockClient($mockClient);

    $request = new ListDatabaseUsersRequest(123);
    $response = $connector->send($request);

    expect($request->createDtoFromResponse($response))->toBeEmpty();
});
