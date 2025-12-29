<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Integrations\Forge\ForgeClient;
use Ddr\ForgeTestBranches\Integrations\Forge\Resources\{DatabaseResource, DatabaseUserResource, SiteResource};

test('throws exception when token is not configured', function (): void {
    config(['forge-test-branches.forge_api_token' => null]);

    new ForgeClient();
})->throws(RuntimeException::class, 'Forge API token not configured');

test('creates client with token from config', function (): void {
    config(['forge-test-branches.forge_api_token' => 'test-token']);

    $client = new ForgeClient();

    expect($client)->toBeInstanceOf(ForgeClient::class);
});

test('creates client with token passed in constructor', function (): void {
    config(['forge-test-branches.forge_api_token' => null]);

    $client = new ForgeClient('custom-token');

    expect($client)->toBeInstanceOf(ForgeClient::class);
});

test('returns SiteResource instance', function (): void {
    $client = new ForgeClient('test-token');

    expect($client->sites())->toBeInstanceOf(SiteResource::class);
});

test('returns DatabaseResource instance', function (): void {
    $client = new ForgeClient('test-token');

    expect($client->databases())->toBeInstanceOf(DatabaseResource::class);
});

test('returns DatabaseUserResource instance', function (): void {
    $client = new ForgeClient('test-token');

    expect($client->databaseUsers())->toBeInstanceOf(DatabaseUserResource::class);
});
