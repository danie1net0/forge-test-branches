<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\{CreateDatabaseUserData, DatabaseUserData};
use Ddr\ForgeTestBranches\Exceptions\ResourceTimeoutException;
use Ddr\ForgeTestBranches\Integrations\Forge\ForgeConnector;
use Ddr\ForgeTestBranches\Integrations\Forge\Requests\Databases\{CreateDatabaseUserRequest, DeleteDatabaseUserRequest, GetDatabaseUserRequest, ListDatabaseUsersRequest};
use Ddr\ForgeTestBranches\Integrations\Forge\Resources\DatabaseUserResource;
use Illuminate\Support\Sleep;
use Saloon\Http\Faking\{MockClient, MockResponse};

function makeDatabaseUserResource(MockClient $mockClient): DatabaseUserResource
{
    $connector = new ForgeConnector('test-token', 'test-org');
    $connector->withMockClient($mockClient);

    return new DatabaseUserResource($connector);
}

/** @return array<string, mixed> */
function makeDatabaseUserResourcePayload(int $id, string $name, string $status = 'installed'): array
{
    return forgeResource('databaseUsers', $id, ['name' => $name, 'status' => $status, 'created_at' => '2025-07-29T09:00:00Z']);
}

test('lista usuários de database do servidor', function (): void {
    $mockClient = new MockClient([
        ListDatabaseUsersRequest::class => MockResponse::make(forgeCollection([
            makeDatabaseUserResourcePayload(1, 'user_one'),
            makeDatabaseUserResourcePayload(2, 'user_two'),
        ])),
    ]);

    $result = makeDatabaseUserResource($mockClient)->list(123);

    expect($result)->toHaveCount(2)
        ->and($result[0])->toBeInstanceOf(DatabaseUserData::class)
        ->name->toBe('user_one')
        ->and($result[1])->toBeInstanceOf(DatabaseUserData::class)
        ->name->toBe('user_two');
});

test('obtém usuário de database por id', function (): void {
    $mockClient = new MockClient([
        GetDatabaseUserRequest::class => MockResponse::make(forgeDocument(makeDatabaseUserResourcePayload(3, 'user_one'))),
    ]);

    expect(makeDatabaseUserResource($mockClient)->get(123, 3))
        ->id->toBe(3)
        ->serverId->toBe(123)
        ->and($mockClient->getLastPendingRequest()->getUrl())->toBe('https://forge.laravel.com/api/orgs/test-org/servers/123/database/users/3');
});

test('encontra usuário de database pelo nome', function (): void {
    $mockClient = new MockClient([
        ListDatabaseUsersRequest::class => MockResponse::make(forgeCollection([
            makeDatabaseUserResourcePayload(1, 'user_two_old'),
            makeDatabaseUserResourcePayload(2, 'user_two'),
        ])),
    ]);

    $result = makeDatabaseUserResource($mockClient)->findByName(123, 'user_two');

    expect($result)->toBeInstanceOf(DatabaseUserData::class)
        ->id->toBe(2)
        ->name->toBe('user_two')
        ->and($mockClient->getLastPendingRequest()->query()->get('filter[name]'))->toBe('user_two');
});

test('retorna null quando usuário de database não é encontrado', function (): void {
    $mockClient = new MockClient([
        ListDatabaseUsersRequest::class => MockResponse::make(forgeCollection([])),
    ]);

    expect(makeDatabaseUserResource($mockClient)->findByName(123, 'nonexistent'))->toBeNull();
});

test('cria usuário de database e retorna DTO', function (): void {
    $mockClient = new MockClient([
        CreateDatabaseUserRequest::class => MockResponse::make(forgeDocument(makeDatabaseUserResourcePayload(5, 'new_user', 'installing')), 202),
    ]);

    $result = makeDatabaseUserResource($mockClient)->create(123, new CreateDatabaseUserData(name: 'new_user', password: 'secret', databaseIds: [1]));

    expect($result)->toBeInstanceOf(DatabaseUserData::class)
        ->id->toBe(5)
        ->name->toBe('new_user')
        ->status->toBe('installing');
});

test('aguarda instalação do usuário de database, dormindo entre as tentativas', function (): void {
    Sleep::fake();

    $mockClient = new MockClient([
        MockResponse::make(forgeDocument(makeDatabaseUserResourcePayload(5, 'new_user', 'installing'))),
        MockResponse::make(forgeDocument(makeDatabaseUserResourcePayload(5, 'new_user', 'installing'))),
        MockResponse::make(forgeDocument(makeDatabaseUserResourcePayload(5, 'new_user'))),
    ]);

    expect(makeDatabaseUserResource($mockClient)->waitForInstallation(123, 5, 5, 5))
        ->status->toBe('installed');

    Sleep::assertSequence([Sleep::for(5)->seconds(), Sleep::for(5)->seconds()]);
});

test('lança exceção quando instalação do usuário de database expira depois de várias tentativas', function (): void {
    Sleep::fake();

    $mockClient = new MockClient([
        MockResponse::make(forgeDocument(makeDatabaseUserResourcePayload(5, 'new_user', 'installing'))),
        MockResponse::make(forgeDocument(makeDatabaseUserResourcePayload(5, 'new_user', 'installing'))),
        MockResponse::make(forgeDocument(makeDatabaseUserResourcePayload(5, 'new_user', 'installing'))),
    ]);

    $action = fn (): DatabaseUserData => makeDatabaseUserResource($mockClient)->waitForInstallation(123, 5, 3, 1);

    expect($action)->toThrow(ResourceTimeoutException::class, 'Timeout waiting for database user installation (database user 5) after 3 attempts');
    $mockClient->assertSentCount(3);
});

test('deleta usuário de database com sucesso', function (): void {
    $mockClient = new MockClient([
        DeleteDatabaseUserRequest::class => MockResponse::make('', 202),
    ]);

    makeDatabaseUserResource($mockClient)->delete(123, 101);

    $mockClient->assertSent(DeleteDatabaseUserRequest::class);
    expect($mockClient->getLastPendingRequest()->getUrl())->toBe('https://forge.laravel.com/api/orgs/test-org/servers/123/database/users/101');
});
