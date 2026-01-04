<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\SiteData;

test('creates instance with all parameters', function (): void {
    $data = new SiteData(
        id: 456,
        serverId: 123,
        name: 'test.example.com',
        aliases: null,
        directory: '/public',
        wildcards: false,
        status: 'installed',
        repository: 'user/repo',
        repositoryProvider: 'gitlab',
        repositoryBranch: 'main',
        repositoryStatus: 'installed',
        quickDeploy: true,
        deploymentStatus: null,
        projectType: 'php',
        app: null,
        appStatus: null,
        hipchatRoom: null,
        slackChannel: null,
        telegramChatId: null,
        telegramChatTitle: null,
        teamsWebhookUrl: null,
        discordWebhookUrl: null,
        username: 'forge',
        balancingStatus: null,
        createdAt: '2024-01-01 00:00:00',
        deploymentUrl: null,
        isSecured: true,
        phpVersion: 'php83',
        tags: null,
        failureDeploymentEmails: null,
        telegramSecret: null,
        webDirectory: '/public',
    );

    expect($data)
        ->id->toBe(456)
        ->serverId->toBe(123)
        ->name->toBe('test.example.com')
        ->directory->toBe('/public')
        ->repository->toBe('user/repo')
        ->repositoryProvider->toBe('gitlab')
        ->quickDeploy->toBeTrue()
        ->isSecured->toBeTrue()
        ->phpVersion->toBe('php83');
});
