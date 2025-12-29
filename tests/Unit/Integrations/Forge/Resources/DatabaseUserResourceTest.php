<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Integrations\Forge\ForgeConnector;
use Ddr\ForgeTestBranches\Integrations\Forge\Requests\Databases\DeleteDatabaseUserRequest;
use Ddr\ForgeTestBranches\Integrations\Forge\Resources\DatabaseUserResource;
use Saloon\Http\Faking\{MockClient, MockResponse};

test('deletes database user successfully', function (): void {
    $mockClient = new MockClient([
        DeleteDatabaseUserRequest::class => MockResponse::make([]),
    ]);

    $connector = new ForgeConnector('test-token');
    $connector->withMockClient($mockClient);

    $resource = new DatabaseUserResource($connector);
    $resource->delete(123, 101);

    $mockClient->assertSent(DeleteDatabaseUserRequest::class);
});
