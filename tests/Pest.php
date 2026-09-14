<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

/**
 * @param array<string, mixed> $attributes
 * @return array<string, mixed>
 */
function forgeResource(string $type, int $id, array $attributes): array
{
    return [
        'id' => (string) $id,
        'type' => $type,
        'attributes' => $attributes,
        'links' => ['self' => ['href' => "https://forge.laravel.com/api/{$type}/{$id}"]],
    ];
}

/**
 * A single-resource JSON:API document (a GET/POST for one record). Unlike a
 * collection response, it carries no pagination `links`/`meta`.
 *
 * @param array<string, mixed> $data
 * @return array<string, mixed>
 */
function forgeDocument(array $data): array
{
    return ['data' => $data];
}

/**
 * A collection JSON:API document (a paginated list response).
 *
 * @param array<int, array<string, mixed>> $data
 * @return array<string, mixed>
 */
function forgeCollection(array $data, ?string $nextCursor = null): array
{
    return [
        'data' => $data,
        'links' => ['first' => null, 'last' => null, 'prev' => null, 'next' => null],
        'meta' => ['per_page' => 30, 'next_cursor' => $nextCursor, 'prev_cursor' => null],
    ];
}

/**
 * @param array<string, mixed> $overrides
 * @return array<string, mixed>
 */
function forgeSiteAttributes(string $name = 'test.example.com', array $overrides = []): array
{
    return [
        'name' => $name,
        'status' => 'installed',
        'url' => "https://{$name}",
        'user' => 'forge',
        'https' => false,
        'web_directory' => '/public',
        'root_directory' => "/home/forge/{$name}",
        'aliases' => [],
        'php_version' => 'php84',
        'deployment_status' => null,
        'quick_deploy' => true,
        'isolated' => false,
        'repository' => [
            'provider' => 'gitlab',
            'url' => 'user/repo',
            'branch' => 'main',
            'status' => 'installed',
        ],
        'app_type' => 'Laravel',
        'created_at' => '2025-07-29T09:00:00Z',
        'updated_at' => '2025-07-29T09:00:00Z',
        ...$overrides,
    ];
}

/**
 * @param array<string, mixed> $overrides
 * @return array<string, mixed>
 */
function forgeDatabaseAttributes(string $name = 'review_feat_login', array $overrides = []): array
{
    return [
        'name' => $name,
        'status' => 'installed',
        'created_at' => '2025-07-29T09:00:00Z',
        'updated_at' => '2025-07-30T09:00:00Z',
        ...$overrides,
    ];
}

/**
 * @param array<string, mixed> $overrides
 * @return array<string, mixed>
 */
function forgeDatabaseUserAttributes(string $name = 'review_feat_login', array $overrides = []): array
{
    return [
        'name' => $name,
        'status' => 'installed',
        'created_at' => '2025-07-29T09:00:00Z',
        'updated_at' => '2025-07-30T09:00:00Z',
        ...$overrides,
    ];
}

/**
 * @param array<string, mixed> $overrides
 * @return array<string, mixed>
 */
function forgeDomainAttributes(string $name = 'feat-login.review.example.com', array $overrides = []): array
{
    return [
        'name' => $name,
        'type' => 'primary',
        'status' => 'enabled',
        'www_redirect_type' => null,
        'allow_wildcard_subdomains' => false,
        'created_at' => '2025-07-29T09:00:00Z',
        'updated_at' => '2025-07-30T09:00:00Z',
        ...$overrides,
    ];
}

/**
 * @param array<string, mixed> $overrides
 * @return array<string, mixed>
 */
function forgeCertificateAttributes(array $overrides = []): array
{
    return [
        'type' => 'letsencrypt',
        'verification_method' => 'http-01',
        'key_type' => 'ecdsa',
        'preferred_chain' => null,
        'request_status' => 'created',
        'status' => 'installed',
        'active' => true,
        'created_at' => '2025-07-29T09:00:00Z',
        'updated_at' => '2025-07-30T09:00:00Z',
        ...$overrides,
    ];
}
