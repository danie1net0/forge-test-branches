<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Integrations\Forge\ForgeConnector;
use Ddr\ForgeTestBranches\Integrations\Forge\Requests\Databases\DeleteDatabaseRequest;
use Ddr\ForgeTestBranches\Integrations\Forge\Resources\DatabaseResource;
use Saloon\Http\Faking\{MockClient, MockResponse};

test('deletes database successfully', function (): void {
    $mockClient = new MockClient([
        DeleteDatabaseRequest::class => MockResponse::make([]),
    ]);

    $connector = new ForgeConnector('test-token');
    $connector->withMockClient($mockClient);

    $resource = new DatabaseResource($connector);
    $resource->delete(123, 789);

    $mockClient->assertSent(DeleteDatabaseRequest::class);
});
