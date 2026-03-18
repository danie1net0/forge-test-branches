<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Services\DomainBuilder;

test('builds domain with configured pattern', function (): void {
    config([
        'forge-test-branches.domain.pattern' => '{branch}.{base}',
        'forge-test-branches.domain.base' => 'review.example.com',
    ]);

    $builder = new DomainBuilder();

    expect($builder->build('feat-hu-123'))->toBe('feat-hu-123.review.example.com');
});

test('supports custom pattern', function (): void {
    config([
        'forge-test-branches.domain.pattern' => 'review-{branch}.{base}',
        'forge-test-branches.domain.base' => 'example.com',
    ]);

    $builder = new DomainBuilder();

    expect($builder->build('feat-hu-123'))->toBe('review-feat-hu-123.example.com');
});

test('extrai slug de domínio com padrão default', function (): void {
    config([
        'forge-test-branches.domain.pattern' => '{branch}.{base}',
        'forge-test-branches.domain.base' => 'review.example.com',
    ]);

    $builder = new DomainBuilder();

    expect($builder->extractSlugFromDomain('feat-hu-123.review.example.com'))->toBe('feat-hu-123');
});

test('extrai slug de domínio com padrão customizado', function (): void {
    config([
        'forge-test-branches.domain.pattern' => 'review-{branch}.{base}',
        'forge-test-branches.domain.base' => 'example.com',
    ]);

    $builder = new DomainBuilder();

    expect($builder->extractSlugFromDomain('review-feat-hu-123.example.com'))->toBe('feat-hu-123');
});

test('retorna null para domínio que não corresponde ao padrão', function (): void {
    config([
        'forge-test-branches.domain.pattern' => '{branch}.{base}',
        'forge-test-branches.domain.base' => 'review.example.com',
    ]);

    $builder = new DomainBuilder();

    expect($builder->extractSlugFromDomain('other.domain.com'))->toBeNull()
        ->and($builder->extractSlugFromDomain('review.example.com'))->toBeNull();
});

test('extractSlugFromDomain é o inverso de build', function (): void {
    config([
        'forge-test-branches.domain.pattern' => '{branch}.{base}',
        'forge-test-branches.domain.base' => 'review.example.com',
    ]);

    $builder = new DomainBuilder();
    $slug = 'feat-login';

    expect($builder->extractSlugFromDomain($builder->build($slug)))->toBe($slug);
});
