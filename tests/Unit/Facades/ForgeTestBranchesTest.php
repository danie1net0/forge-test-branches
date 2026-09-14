<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Facades\ForgeTestBranches;

beforeEach(function (): void {
    config([
        'forge-test-branches.forge_api_token' => 'fake-token',
        'forge-test-branches.organization' => 'test-org',
    ]);
});

test('retorna o accessor correto da facade', function (): void {
    expect(ForgeTestBranches::getFacadeRoot())
        ->toBeInstanceOf(Ddr\ForgeTestBranches\ForgeTestBranches::class);
});
