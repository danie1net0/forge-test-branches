<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\SiteData;
use Ddr\ForgeTestBranches\Integrations\Forge\ForgeConnector;
use Ddr\ForgeTestBranches\Integrations\Forge\Requests\Sites\GetSiteRequest;
use Saloon\Http\Faking\{MockClient, MockResponse};

test('resolve endpoint corretamente', function (): void {
    $request = new GetSiteRequest(123, 456);

    expect($request->resolveEndpoint())->toBe('/servers/123/sites/456');
});

test('usa método GET', function (): void {
    $request = new GetSiteRequest(123, 456);

    expect($request->getMethod()->value)->toBe('GET');
});

test('retorna SiteData do response', function (): void {
    $mockClient = new MockClient([
        GetSiteRequest::class => MockResponse::make([
            'site' => [
                'id' => 456,
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
            ],
        ]),
    ]);

    $connector = new ForgeConnector('test-token');
    $connector->withMockClient($mockClient);

    $request = new GetSiteRequest(123, 456);
    $response = $connector->send($request);
    $result = $request->createDtoFromResponse($response);

    expect($result)->toBeInstanceOf(SiteData::class)
        ->id->toBe(456)
        ->serverId->toBe(123)
        ->name->toBe('test.example.com')
        ->status->toBe('installed');
});
