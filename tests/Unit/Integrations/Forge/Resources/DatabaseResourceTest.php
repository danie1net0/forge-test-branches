<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\{CreateDatabaseData, DatabaseData};
use Ddr\ForgeTestBranches\Exceptions\ResourceTimeoutException;
use Ddr\ForgeTestBranches\Integrations\Forge\ForgeConnector;
use Ddr\ForgeTestBranches\Integrations\Forge\Requests\Databases\{CreateDatabaseRequest, DeleteDatabaseRequest, GetDatabaseRequest, ListDatabasesRequest};
use Ddr\ForgeTestBranches\Integrations\Forge\Resources\DatabaseResource;
use Illuminate\Support\Sleep;
use Saloon\Http\Faking\{MockClient, MockResponse};

function makeDatabaseResource(MockClient $mockClient): DatabaseResource
{
    $connector = new ForgeConnector('test-token', 'test-org');
    $connector->withMockClient($mockClient);

    return new DatabaseResource($connector);
}

/** @return array<string, mixed> */
function makeDatabaseResourcePayload(int $id, string $name, string $status = 'installed'): array
{
    return forgeResource('databases', $id, ['name' => $name, 'status' => $status, 'created_at' => '2025-07-29T09:00:00Z']);
}

test('lista databases do servidor', function (): void {
    $mockClient = new MockClient([
        ListDatabasesRequest::class => MockResponse::make(forgeCollection([
            makeDatabaseResourcePayload(1, 'db_one'),
            makeDatabaseResourcePayload(2, 'db_two'),
        ])),
    ]);

    $result = makeDatabaseResource($mockClient)->list(123);

    expect($result)->toHaveCount(2)
        ->and($result[0])->toBeInstanceOf(DatabaseData::class)
        ->name->toBe('db_one')
        ->and($result[1])->toBeInstanceOf(DatabaseData::class)
        ->name->toBe('db_two');
});

test('obtém database por id', function (): void {
    $mockClient = new MockClient([
        GetDatabaseRequest::class => MockResponse::make(forgeDocument(makeDatabaseResourcePayload(7, 'db_one'))),
    ]);

    expect(makeDatabaseResource($mockClient)->get(123, 7))
        ->id->toBe(7)
        ->serverId->toBe(123)
        ->and($mockClient->getLastPendingRequest()->getUrl())->toBe('https://forge.laravel.com/api/orgs/test-org/servers/123/database/schemas/7');
});

test('encontra database pelo nome', function (): void {
    $mockClient = new MockClient([
        ListDatabasesRequest::class => MockResponse::make(forgeCollection([
            makeDatabaseResourcePayload(1, 'db_two_old'),
            makeDatabaseResourcePayload(2, 'db_two'),
        ])),
    ]);

    $result = makeDatabaseResource($mockClient)->findByName(123, 'db_two');

    expect($result)->toBeInstanceOf(DatabaseData::class)
        ->id->toBe(2)
        ->name->toBe('db_two')
        ->and($mockClient->getLastPendingRequest()->query()->get('filter[name]'))->toBe('db_two');
});

test('retorna null quando database não é encontrada pelo nome', function (): void {
    $mockClient = new MockClient([
        ListDatabasesRequest::class => MockResponse::make(forgeCollection([])),
    ]);

    expect(makeDatabaseResource($mockClient)->findByName(123, 'nonexistent'))->toBeNull();
});

test('cria database e retorna DTO', function (): void {
    $mockClient = new MockClient([
        CreateDatabaseRequest::class => MockResponse::make(forgeDocument(makeDatabaseResourcePayload(5, 'new_db', 'installing')), 202),
    ]);

    $result = makeDatabaseResource($mockClient)->create(123, new CreateDatabaseData(name: 'new_db'));

    expect($result)->toBeInstanceOf(DatabaseData::class)
        ->id->toBe(5)
        ->name->toBe('new_db')
        ->status->toBe('installing');
});

test('aguarda instalação da database, dormindo entre as tentativas', function (): void {
    Sleep::fake();

    $mockClient = new MockClient([
        MockResponse::make(forgeDocument(makeDatabaseResourcePayload(5, 'new_db', 'installing'))),
        MockResponse::make(forgeDocument(makeDatabaseResourcePayload(5, 'new_db', 'installing'))),
        MockResponse::make(forgeDocument(makeDatabaseResourcePayload(5, 'new_db'))),
    ]);

    expect(makeDatabaseResource($mockClient)->waitForInstallation(123, 5, 5, 5))
        ->status->toBe('installed');

    Sleep::assertSequence([Sleep::for(5)->seconds(), Sleep::for(5)->seconds()]);
});

test('lança exceção quando instalação da database expira depois de várias tentativas', function (): void {
    Sleep::fake();

    $mockClient = new MockClient([
        MockResponse::make(forgeDocument(makeDatabaseResourcePayload(5, 'new_db', 'installing'))),
        MockResponse::make(forgeDocument(makeDatabaseResourcePayload(5, 'new_db', 'installing'))),
        MockResponse::make(forgeDocument(makeDatabaseResourcePayload(5, 'new_db', 'installing'))),
    ]);

    $action = fn (): DatabaseData => makeDatabaseResource($mockClient)->waitForInstallation(123, 5, 3, 1);

    expect($action)->toThrow(ResourceTimeoutException::class, 'Timeout waiting for database installation (database 5) after 3 attempts');
    $mockClient->assertSentCount(3);
});

test('deleta database com sucesso', function (): void {
    $mockClient = new MockClient([
        DeleteDatabaseRequest::class => MockResponse::make('', 202),
    ]);

    makeDatabaseResource($mockClient)->delete(123, 789);

    $mockClient->assertSent(DeleteDatabaseRequest::class);
    expect($mockClient->getLastPendingRequest()->getUrl())->toBe('https://forge.laravel.com/api/orgs/test-org/servers/123/database/schemas/789');
});
