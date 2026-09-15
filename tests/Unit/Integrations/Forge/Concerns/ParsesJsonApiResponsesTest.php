<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\DatabaseData;
use Ddr\ForgeTestBranches\Exceptions\InvalidApiResponseException;
use Ddr\ForgeTestBranches\Integrations\Forge\ForgeConnector;
use Ddr\ForgeTestBranches\Integrations\Forge\Requests\Databases\{GetDatabaseRequest, ListDatabasesRequest};
use Ddr\ForgeTestBranches\Integrations\Forge\Requests\Sites\GetEnvironmentFileRequest;
use Saloon\Http\Faking\{MockClient, MockResponse};

test('lança exceção quando o campo data está ausente em um recurso único', function (): void {
    $mockClient = new MockClient([
        GetDatabaseRequest::class => MockResponse::make(['meta' => []]),
    ]);

    $connector = new ForgeConnector('test-token', 'acme');
    $connector->withMockClient($mockClient);

    $request = new GetDatabaseRequest(123, 1);
    $response = $connector->send($request);

    expect(fn (): DatabaseData => $request->createDtoFromResponse($response))
        ->toThrow(InvalidApiResponseException::class, 'The Forge API response is missing the expected "data" field.');
});

test('lança exceção quando o campo id está ausente no recurso', function (): void {
    $mockClient = new MockClient([
        GetDatabaseRequest::class => MockResponse::make(['data' => ['type' => 'databases', 'attributes' => []]]),
    ]);

    $connector = new ForgeConnector('test-token', 'acme');
    $connector->withMockClient($mockClient);

    $request = new GetDatabaseRequest(123, 1);
    $response = $connector->send($request);

    expect(fn (): DatabaseData => $request->createDtoFromResponse($response))
        ->toThrow(InvalidApiResponseException::class, 'The Forge API response is missing the expected "id" field.');
});

test('lança exceção quando o campo data está ausente em uma coleção', function (): void {
    $mockClient = new MockClient([
        ListDatabasesRequest::class => MockResponse::make(['meta' => []]),
    ]);

    $connector = new ForgeConnector('test-token', 'acme');
    $connector->withMockClient($mockClient);

    $request = new ListDatabasesRequest(123);
    $response = $connector->send($request);

    expect(fn (): array => $request->createDtoFromResponse($response))
        ->toThrow(InvalidApiResponseException::class, 'The Forge API response is missing the expected "data" field.');
});

test('extraAttributes sobrescreve os atributos e id sempre vence', function (): void {
    $mockClient = new MockClient([
        GetDatabaseRequest::class => MockResponse::make(forgeDocument(forgeResource('databases', 1, forgeDatabaseAttributes('db_one', [
            'server_id' => 999,
        ])))),
    ]);

    $connector = new ForgeConnector('test-token', 'acme');
    $connector->withMockClient($mockClient);

    $request = new GetDatabaseRequest(123, 1);
    $response = $connector->send($request);
    $result = $request->createDtoFromResponse($response);

    expect($result)->toBeInstanceOf(DatabaseData::class)
        ->id->toBe(1)
        ->serverId->toBe(123);
});

test('lê um atributo string simples da resposta', function (): void {
    $mockClient = new MockClient([
        GetEnvironmentFileRequest::class => MockResponse::make(forgeDocument(forgeResource('environments', 456, ['content' => 'APP_ENV=production']))),
    ]);

    $connector = new ForgeConnector('test-token', 'acme');
    $connector->withMockClient($mockClient);

    $request = new GetEnvironmentFileRequest(123, 456);
    $response = $connector->send($request);

    expect($request->createDtoFromResponse($response))->toBe('APP_ENV=production');
});
