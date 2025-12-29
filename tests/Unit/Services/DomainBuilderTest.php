<?php

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
