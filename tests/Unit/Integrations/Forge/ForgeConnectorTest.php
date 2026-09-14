<?php

declare(strict_types=1);

use Saloon\Http\Response;
use Saloon\Exceptions\Request\Statuses\{ServiceUnavailableException, UnprocessableEntityException};
use Ddr\ForgeTestBranches\Integrations\Forge\ForgeConnector;
use Ddr\ForgeTestBranches\Integrations\Forge\Requests\Sites\ListSitesRequest;
use Saloon\Http\Faking\{MockClient, MockResponse};

test('resolves organization scoped v2 base url', function (): void {
    $connector = new ForgeConnector('test-token', 'acme');

    expect($connector->resolveBaseUrl())->toBe('https://forge.laravel.com/api/orgs/acme');
});

test('includes default headers', function (): void {
    $connector = new ForgeConnector('test-token', 'acme');
    $headers = $connector->headers()->all();

    expect($headers)->toHaveKey('Accept', 'application/json');
});

test('authenticates with bearer token', function (): void {
    $mockClient = new MockClient([
        ListSitesRequest::class => MockResponse::make(forgeCollection([])),
    ]);

    $connector = new ForgeConnector('test-token', 'acme');
    $connector->withMockClient($mockClient);
    $connector->send(new ListSitesRequest(123));

    expect($mockClient->getLastPendingRequest()->headers()->get('Authorization'))->toBe('Bearer test-token');
});

test('percorre todas as páginas usando o cursor', function (): void {
    $mockClient = new MockClient([
        MockResponse::make(forgeCollection([forgeResource('sites', 1, forgeSiteAttributes('site1.example.com'))], nextCursor: 'cursor-2')),
        MockResponse::make(forgeCollection([forgeResource('sites', 2, forgeSiteAttributes('site2.example.com'))])),
    ]);

    $connector = new ForgeConnector('test-token', 'acme');
    $connector->withMockClient($mockClient);

    $sites = $connector->sendPaginated(new ListSitesRequest(123));

    expect($sites)->toHaveCount(2)
        ->and($sites[0]->name)->toBe('site1.example.com')
        ->and($sites[1]->name)->toBe('site2.example.com');

    $mockClient->assertSentCount(2);
    expect($mockClient->getLastPendingRequest()->query()->get('page[cursor]'))->toBe('cursor-2');
});

test('a primeira página não envia cursor', function (): void {
    $mockClient = new MockClient([
        MockResponse::make(forgeCollection([forgeResource('sites', 1, forgeSiteAttributes('site1.example.com'))])),
    ]);

    $connector = new ForgeConnector('test-token', 'acme');
    $connector->withMockClient($mockClient);

    $connector->sendPaginated(new ListSitesRequest(123));

    expect($mockClient->getLastPendingRequest()->query()->get('page[cursor]'))->toBeNull();
});

test('mantém o filtro de nome entre as páginas', function (): void {
    $mockClient = new MockClient([
        MockResponse::make(forgeCollection([forgeResource('sites', 1, forgeSiteAttributes('feat-login.example.com'))], nextCursor: 'cursor-2')),
        MockResponse::make(forgeCollection([forgeResource('sites', 2, forgeSiteAttributes('feat-login.example.com'))])),
    ]);

    $connector = new ForgeConnector('test-token', 'acme');
    $connector->withMockClient($mockClient);

    $connector->sendPaginated(new ListSitesRequest(123)->filterByName('feat-login.example.com'));

    $requests = $mockClient->getRecordedResponses();

    expect($requests[0]->getPendingRequest()->query()->get('filter[name]'))->toBe('feat-login.example.com')
        ->and($requests[1]->getPendingRequest()->query()->get('filter[name]'))->toBe('feat-login.example.com');
});

test('encontra o item na segunda página', function (): void {
    $mockClient = new MockClient([
        MockResponse::make(forgeCollection([forgeResource('sites', 1, forgeSiteAttributes('other.example.com'))], nextCursor: 'cursor-2')),
        MockResponse::make(forgeCollection([forgeResource('sites', 2, forgeSiteAttributes('target.example.com'))])),
    ]);

    $connector = new ForgeConnector('test-token', 'acme');
    $connector->withMockClient($mockClient);

    $sites = $connector->sendPaginated(new ListSitesRequest(123));

    expect($sites)->toHaveCount(2);
    $mockClient->assertSentCount(2);
});

test('para de paginar quando o cursor volta a se repetir', function (): void {
    $mockClient = new MockClient([
        MockResponse::make(forgeCollection([forgeResource('sites', 1, forgeSiteAttributes('site1.example.com'))], nextCursor: 'cursor-1')),
        MockResponse::make(forgeCollection([forgeResource('sites', 2, forgeSiteAttributes('site2.example.com'))], nextCursor: 'cursor-1')),
    ]);

    $connector = new ForgeConnector('test-token', 'acme');
    $connector->withMockClient($mockClient);

    $sites = $connector->sendPaginated(new ListSitesRequest(123));

    expect($sites)->toHaveCount(2);
    $mockClient->assertSentCount(2);
});

test('tenta novamente quando a API responde 429', function (): void {
    $mockClient = new MockClient([
        MockResponse::make(['message' => 'Too Many Attempts.'], 429),
        MockResponse::make(forgeCollection([])),
    ]);

    $connector = new ForgeConnector('test-token', 'acme');
    $connector->retryInterval = 1;
    $connector->withMockClient($mockClient);

    $connector->send(new ListSitesRequest(123));

    $mockClient->assertSentCount(2);
});

test('tenta novamente quando a API responde 503', function (): void {
    $mockClient = new MockClient([
        MockResponse::make(['message' => 'Service Unavailable'], 503),
        MockResponse::make(forgeCollection([])),
    ]);

    $connector = new ForgeConnector('test-token', 'acme');
    $connector->retryInterval = 1;
    $connector->withMockClient($mockClient);

    $connector->send(new ListSitesRequest(123));

    $mockClient->assertSentCount(2);
});

test('não tenta novamente em erros de cliente como 422', function (): void {
    $mockClient = new MockClient([
        MockResponse::make(['message' => 'Unprocessable'], 422),
    ]);

    $connector = new ForgeConnector('test-token', 'acme');
    $connector->retryInterval = 1;
    $connector->withMockClient($mockClient);

    expect(fn (): Response => $connector->send(new ListSitesRequest(123)))
        ->toThrow(UnprocessableEntityException::class);

    $mockClient->assertSentCount(1);
});

test('desiste após esgotar as tentativas em falhas persistentes de servidor', function (): void {
    $mockClient = new MockClient([
        MockResponse::make(['message' => 'Service Unavailable'], 503),
        MockResponse::make(['message' => 'Service Unavailable'], 503),
        MockResponse::make(['message' => 'Service Unavailable'], 503),
    ]);

    $connector = new ForgeConnector('test-token', 'acme');
    $connector->retryInterval = 1;
    $connector->withMockClient($mockClient);

    expect(fn (): Response => $connector->send(new ListSitesRequest(123)))
        ->toThrow(ServiceUnavailableException::class);

    $mockClient->assertSentCount(3);
});
