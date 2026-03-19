<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\{CreateSiteData, SiteData};
use Ddr\ForgeTestBranches\Integrations\Forge\ForgeConnector;
use Ddr\ForgeTestBranches\Integrations\Forge\Requests\Sites\CreateSiteRequest;
use Saloon\Http\Faking\{MockClient, MockResponse};

test('resolve endpoint corretamente', function (): void {
    $data = new CreateSiteData(domain: 'test.example.com', projectType: 'php');
    $request = new CreateSiteRequest(123, $data);

    expect($request->resolveEndpoint())->toBe('/servers/123/sites');
});

test('usa método POST', function (): void {
    $data = new CreateSiteData(domain: 'test.example.com', projectType: 'php');
    $request = new CreateSiteRequest(123, $data);

    expect($request->getMethod()->value)->toBe('POST');
});

test('cria site e retorna DTO correto', function (): void {
    $mockClient = new MockClient([
        CreateSiteRequest::class => MockResponse::make([
            'site' => [
                'id' => 100,
                'name' => 'test.example.com',
                'aliases' => null,
                'directory' => '/public',
                'wildcards' => false,
                'status' => 'installing',
                'repository' => null,
                'repository_provider' => null,
                'repository_branch' => null,
                'repository_status' => null,
                'quick_deploy' => false,
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

    $data = new CreateSiteData(domain: 'test.example.com', projectType: 'php');
    $request = new CreateSiteRequest(123, $data);
    $response = $connector->send($request);
    $result = $request->createDtoFromResponse($response);

    expect($result)->toBeInstanceOf(SiteData::class)
        ->id->toBe(100)
        ->serverId->toBe(123)
        ->name->toBe('test.example.com')
        ->status->toBe('installing');
});
