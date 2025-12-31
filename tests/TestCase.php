<?php

namespace Ddr\ForgeTestBranches\Tests;

use Override;
use Orchestra\Testbench\TestCase as Orchestra;
use Ddr\ForgeTestBranches\ForgeTestBranchesServiceProvider;

class TestCase extends Orchestra
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            ForgeTestBranchesServiceProvider::class,
        ];
    }
}
