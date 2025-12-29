<?php

use Ddr\ForgeTestBranches\Services\BranchSanitizer;

test('sanitizes branch with special characters', function (): void {
    $sanitizer = new BranchSanitizer();

    expect($sanitizer->sanitize('feat/HU-123_teste'))->toBe('feat-hu-123-teste');
});

test('sanitizes branch with slashes', function (): void {
    $sanitizer = new BranchSanitizer();

    expect($sanitizer->sanitize('feature/add-new-feature'))->toBe('feature-add-new-feature');
});

test('removes duplicate slashes', function (): void {
    $sanitizer = new BranchSanitizer();

    expect($sanitizer->sanitize('feat//teste'))->toBe('feat-teste');
});

test('removes hyphens from start and end', function (): void {
    $sanitizer = new BranchSanitizer();

    expect($sanitizer->sanitize('-feat-teste-'))->toBe('feat-teste');
});

test('converts to lowercase', function (): void {
    $sanitizer = new BranchSanitizer();

    expect($sanitizer->sanitize('FEAT-TESTE'))->toBe('feat-teste');
});

test('limits maximum length to 63 characters', function (): void {
    $sanitizer = new BranchSanitizer();

    expect(mb_strlen($sanitizer->sanitize(str_repeat('a', 100))))->toBe(63);
});
