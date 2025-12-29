<?php

namespace Ddr\ForgeTestBranches\Tests;

use Override;
use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use Ddr\ForgeTestBranches\ForgeTestBranchesServiceProvider;

class TestCase extends Orchestra
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName): string => 'Ddr\\ForgeTestBranches\\Database\\Factories\\' . class_basename($modelName) . 'Factory'
        );
    }

    protected function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');

        $migration = include __DIR__ . '/../database/migrations/create_review_environments_table.php.stub';
        $migration->up();
    }

    protected function getPackageProviders($app): array
    {
        return [
            ForgeTestBranchesServiceProvider::class,
        ];
    }
}
