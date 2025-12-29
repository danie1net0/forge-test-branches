<?php

declare(strict_types=1);

use Laravel\Prompts\Prompt;

beforeEach(function (): void {
    config(['forge-test-branches.forge_api_token' => 'fake-token']);
    Prompt::fallbackWhen(true);
});

test('skips env configuration when user declines', function (): void {
    $this->artisan('forge-test-branches:install')
        ->expectsConfirmation('Would you like to configure environment variables now?', 'no')
        ->expectsConfirmation('Would you like to add GitLab CI/CD configuration?', 'no')
        ->assertExitCode(0);
});

test('displays error when env file does not exist', function (): void {
    $envPath = base_path('.env');
    $envBackup = null;

    if (file_exists($envPath)) {
        $envBackup = file_get_contents($envPath);
        unlink($envPath);
    }

    try {
        $this->artisan('forge-test-branches:install')
            ->expectsConfirmation('Would you like to configure environment variables now?', 'yes')
            ->expectsConfirmation('Would you like to add GitLab CI/CD configuration?', 'no')
            ->assertExitCode(0);
    } finally {
        if ($envBackup !== null) {
            file_put_contents($envPath, $envBackup);
        }
    }
});

test('adds variables to env successfully', function (): void {
    $envPath = base_path('.env');
    $envBackup = file_exists($envPath) ? file_get_contents($envPath) : null;
    file_put_contents($envPath, "APP_NAME=Test\n");

    try {
        $this->artisan('forge-test-branches:install')
            ->expectsConfirmation('Would you like to configure environment variables now?', 'yes')
            ->expectsQuestion('FORGE_API_TOKEN (Forge API Token)', 'test-token')
            ->expectsQuestion('FORGE_SERVER_ID (Server ID on Forge)', '123')
            ->expectsQuestion('FORGE_REVIEW_DOMAIN (Base domain for review apps)', 'review.test.com')
            ->expectsChoice('FORGE_GIT_PROVIDER (Git Provider)', 'GitLab', ['Bitbucket', 'GitHub', 'GitLab', 'bitbucket', 'github', 'gitlab'])
            ->expectsQuestion('FORGE_GIT_REPOSITORY (Repository in user/repo format)', 'user/repo')
            ->expectsQuestion('FORGE_WEBHOOK_SECRET (Secret to validate webhooks - optional)', 'secret123')
            ->expectsConfirmation('Would you like to add GitLab CI/CD configuration?', 'no')
            ->assertExitCode(0);

        $envContent = file_get_contents($envPath);
        expect($envContent)
            ->toContain('FORGE_API_TOKEN=test-token')
            ->toContain('FORGE_SERVER_ID=123')
            ->toContain('FORGE_REVIEW_DOMAIN=review.test.com')
            ->toContain('FORGE_GIT_PROVIDER=GitLab')
            ->toContain('FORGE_GIT_REPOSITORY=user/repo')
            ->toContain('FORGE_WEBHOOK_SECRET=secret123');
    } finally {
        if ($envBackup !== null) {
            file_put_contents($envPath, $envBackup);
        } else {
            unlink($envPath);
        }
    }
});

test('does not add variables when they already exist in env', function (): void {
    $envPath = base_path('.env');
    $envBackup = file_exists($envPath) ? file_get_contents($envPath) : null;
    $existingEnv = "APP_NAME=Test\nFORGE_API_TOKEN=existing\nFORGE_SERVER_ID=999\nFORGE_REVIEW_DOMAIN=old.com\nFORGE_GIT_PROVIDER=github\nFORGE_GIT_REPOSITORY=old/repo\nFORGE_WEBHOOK_SECRET=oldsecret\n";
    file_put_contents($envPath, $existingEnv);

    try {
        $this->artisan('forge-test-branches:install')
            ->expectsConfirmation('Would you like to configure environment variables now?', 'yes')
            ->expectsQuestion('FORGE_API_TOKEN (Forge API Token)', 'new-token')
            ->expectsQuestion('FORGE_SERVER_ID (Server ID on Forge)', '123')
            ->expectsQuestion('FORGE_REVIEW_DOMAIN (Base domain for review apps)', 'new.test.com')
            ->expectsChoice('FORGE_GIT_PROVIDER (Git Provider)', 'GitLab', ['Bitbucket', 'GitHub', 'GitLab', 'bitbucket', 'github', 'gitlab'])
            ->expectsQuestion('FORGE_GIT_REPOSITORY (Repository in user/repo format)', 'new/repo')
            ->expectsQuestion('FORGE_WEBHOOK_SECRET (Secret to validate webhooks - optional)', 'newsecret')
            ->expectsConfirmation('Would you like to add GitLab CI/CD configuration?', 'no')
            ->assertExitCode(0);

        $envContent = file_get_contents($envPath);
        expect($envContent)->toContain('FORGE_API_TOKEN=existing')
            ->toContain('FORGE_SERVER_ID=999');
    } finally {
        if ($envBackup !== null) {
            file_put_contents($envPath, $envBackup);
        } else {
            unlink($envPath);
        }
    }
});

test('generates webhook secret automatically when empty', function (): void {
    $envPath = base_path('.env');
    $envBackup = file_exists($envPath) ? file_get_contents($envPath) : null;
    file_put_contents($envPath, "APP_NAME=Test\n");

    try {
        $this->artisan('forge-test-branches:install')
            ->expectsConfirmation('Would you like to configure environment variables now?', 'yes')
            ->expectsQuestion('FORGE_API_TOKEN (Forge API Token)', 'test-token')
            ->expectsQuestion('FORGE_SERVER_ID (Server ID on Forge)', '123')
            ->expectsQuestion('FORGE_REVIEW_DOMAIN (Base domain for review apps)', 'review.test.com')
            ->expectsChoice('FORGE_GIT_PROVIDER (Git Provider)', 'GitLab', ['Bitbucket', 'GitHub', 'GitLab', 'bitbucket', 'github', 'gitlab'])
            ->expectsQuestion('FORGE_GIT_REPOSITORY (Repository in user/repo format)', 'user/repo')
            ->expectsQuestion('FORGE_WEBHOOK_SECRET (Secret to validate webhooks - optional)', '')
            ->expectsConfirmation('Would you like to add GitLab CI/CD configuration?', 'no')
            ->assertExitCode(0);

        $envContent = file_get_contents($envPath);
        expect($envContent)->toMatch('/FORGE_WEBHOOK_SECRET=[a-f0-9]{32}/');
    } finally {
        if ($envBackup !== null) {
            file_put_contents($envPath, $envBackup);
        } else {
            unlink($envPath);
        }
    }
});

test('creates gitlab-ci file when it does not exist', function (): void {
    $ciPath = base_path('.gitlab-ci.yml');
    $ciBackup = file_exists($ciPath) ? file_get_contents($ciPath) : null;

    if (file_exists($ciPath)) {
        unlink($ciPath);
    }

    try {
        $this->artisan('forge-test-branches:install')
            ->expectsConfirmation('Would you like to configure environment variables now?', 'no')
            ->expectsConfirmation('Would you like to add GitLab CI/CD configuration?', 'yes')
            ->expectsQuestion('Domain for review apps in CI', 'review.test.com')
            ->assertExitCode(0);

        expect(file_exists($ciPath))->toBeTrue();

        $ciContent = file_get_contents($ciPath);
        expect($ciContent)
            ->toContain('review.test.com')
            ->toContain('forge-test-branches');
    } finally {
        if ($ciBackup !== null) {
            file_put_contents($ciPath, $ciBackup);
        } elseif (file_exists($ciPath)) {
            unlink($ciPath);
        }
    }
});

test('adds configuration to existing gitlab-ci', function (): void {
    $ciPath = base_path('.gitlab-ci.yml');
    $ciBackup = file_exists($ciPath) ? file_get_contents($ciPath) : null;
    file_put_contents($ciPath, "stages:\n  - test\n\ntest_job:\n  script: echo test\n");

    try {
        $this->artisan('forge-test-branches:install')
            ->expectsConfirmation('Would you like to configure environment variables now?', 'no')
            ->expectsConfirmation('Would you like to add GitLab CI/CD configuration?', 'yes')
            ->expectsQuestion('Domain for review apps in CI', 'review.test.com')
            ->assertExitCode(0);

        $ciContent = file_get_contents($ciPath);
        expect($ciContent)
            ->toContain('- review')
            ->toContain('test_job')
            ->toContain('review.test.com');
    } finally {
        if ($ciBackup !== null) {
            file_put_contents($ciPath, $ciBackup);
        } else {
            unlink($ciPath);
        }
    }
});

test('warns when gitlab configuration already exists', function (): void {
    $ciPath = base_path('.gitlab-ci.yml');
    $ciBackup = file_exists($ciPath) ? file_get_contents($ciPath) : null;
    file_put_contents($ciPath, "stages:\n  - review\n\nforge-test-branches:\n  stage: review\n");

    try {
        $this->artisan('forge-test-branches:install')
            ->expectsConfirmation('Would you like to configure environment variables now?', 'no')
            ->expectsConfirmation('Would you like to add GitLab CI/CD configuration?', 'yes')
            ->expectsQuestion('Domain for review apps in CI', 'review.test.com')
            ->assertExitCode(0);

        $ciContent = file_get_contents($ciPath);
        expect($ciContent)->toBe("stages:\n  - review\n\nforge-test-branches:\n  stage: review\n");
    } finally {
        if ($ciBackup !== null) {
            file_put_contents($ciPath, $ciBackup);
        } else {
            unlink($ciPath);
        }
    }
});
