<?php

namespace Ddr\ForgeTestBranches\Tests;

use Override;
use Orchestra\Testbench\TestCase as Orchestra;
use Ddr\ForgeTestBranches\ForgeTestBranchesServiceProvider;
use Spatie\LaravelData\LaravelDataServiceProvider;

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
            LaravelDataServiceProvider::class,
            ForgeTestBranchesServiceProvider::class,
        ];
    }
}
