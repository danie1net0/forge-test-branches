<?php

namespace Ddr\ForgeTestBranches;

use Spatie\LaravelPackageTools\{Package, PackageServiceProvider};
use Ddr\ForgeTestBranches\Commands\ForgeTestBranchesCommand;

class ForgeTestBranchesServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('forge-test-branches')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_forge_test_branches_table')
            ->hasCommand(ForgeTestBranchesCommand::class);
    }
}
