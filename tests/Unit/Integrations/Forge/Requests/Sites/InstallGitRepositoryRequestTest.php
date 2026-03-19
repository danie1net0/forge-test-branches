<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\{InstallGitRepositoryData, SiteData};
use Ddr\ForgeTestBranches\Integrations\Forge\ForgeConnector;
use Ddr\ForgeTestBranches\Integrations\Forge\Requests\Sites\InstallGitRepositoryRequest;
use Saloon\Http\Faking\{MockClient, MockResponse};

test('resolve endpoint corretamente', function (): void {
    $data = new InstallGitRepositoryData(provider: 'gitlab', repository: 'user/repo', branch: 'main');
    $request = new InstallGitRepositoryRequest(123, 456, $data);

    expect($request->resolveEndpoint())->toBe('/servers/123/sites/456/git');
});

test('usa método POST', function (): void {
    $data = new InstallGitRepositoryData(provider: 'gitlab', repository: 'user/repo');
    $request = new InstallGitRepositoryRequest(123, 456, $data);

    expect($request->getMethod()->value)->toBe('POST');
});

test('instala repositório git e retorna SiteData', function (): void {
    $mockClient = new MockClient([
        InstallGitRepositoryRequest::class => MockResponse::make([
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
                'repository_status' => 'installing',
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

    $data = new InstallGitRepositoryData(provider: 'gitlab', repository: 'user/repo', branch: 'main');
    $request = new InstallGitRepositoryRequest(123, 456, $data);
    $response = $connector->send($request);
    $result = $request->createDtoFromResponse($response);

    expect($result)->toBeInstanceOf(SiteData::class)
        ->id->toBe(456)
        ->serverId->toBe(123)
        ->repositoryStatus->toBe('installing');
});
