<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\SiteData;
use Ddr\ForgeTestBranches\Integrations\Forge\ForgeConnector;
use Ddr\ForgeTestBranches\Integrations\Forge\Requests\Sites\ListSitesRequest;
use Saloon\Http\Faking\{MockClient, MockResponse};

test('resolve endpoint corretamente', function (): void {
    $request = new ListSitesRequest(123);

    expect($request->resolveEndpoint())->toBe('/servers/123/sites');
});

test('usa método GET', function (): void {
    $request = new ListSitesRequest(123);

    expect($request->getMethod()->value)->toBe('GET');
});

test('lista sites e retorna array de DTOs', function (): void {
    $sitePayload = [
        'name' => 'test.example.com',
        'aliases' => null,
        'directory' => '/public',
        'wildcards' => false,
        'status' => 'installed',
        'repository' => 'user/repo',
        'repository_provider' => 'gitlab',
        'repository_branch' => 'main',
        'repository_status' => 'installed',
        'quick_deploy' => true,
        'deployment_status' => null,
        'project_type' => 'php',
        'app' => null,
        'app_status' => null,
        'hipchat_room' => null,
        'slack_channel' => null,
        'telegram_chat_id' => null,
        'telegram_chat_title' => null,
        'teams_webhook_url' => null,
        'discord_webhook_url' => null,
        'username' => 'forge',
        'balancing_status' => null,
        'created_at' => '2024-01-01 00:00:00',
        'deployment_url' => null,
        'is_secured' => false,
        'php_version' => 'php84',
        'tags' => null,
        'failure_deployment_emails' => null,
        'telegram_secret' => null,
        'web_directory' => '/public',
    ];

    $mockClient = new MockClient([
        ListSitesRequest::class => MockResponse::make([
            'sites' => [
                array_merge(['id' => 1], $sitePayload),
                array_merge(['id' => 2], $sitePayload, ['name' => 'site2.example.com']),
            ],
        ]),
    ]);

    $connector = new ForgeConnector('test-token');
    $connector->withMockClient($mockClient);

    $request = new ListSitesRequest(123);
    $response = $connector->send($request);
    $result = $request->createDtoFromResponse($response);

    expect($result)->toHaveCount(2)
        ->and($result[0])->toBeInstanceOf(SiteData::class)
        ->id->toBe(1)
        ->serverId->toBe(123)
        ->name->toBe('test.example.com')
        ->and($result[1])->toBeInstanceOf(SiteData::class)
        ->id->toBe(2)
        ->name->toBe('site2.example.com');
});

test('retorna array vazio quando não há sites', function (): void {
    $mockClient = new MockClient([
        ListSitesRequest::class => MockResponse::make([
            'sites' => null,
        ]),
    ]);

    $connector = new ForgeConnector('test-token');
    $connector->withMockClient($mockClient);

    $request = new ListSitesRequest(123);
    $response = $connector->send($request);
    $result = $request->createDtoFromResponse($response);

    expect($result)->toBeEmpty();
});
