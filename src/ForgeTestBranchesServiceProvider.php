<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches;

use Ddr\ForgeTestBranches\Integrations\Forge\ForgeClient;
use Spatie\LaravelPackageTools\{Package, PackageServiceProvider};
use Ddr\ForgeTestBranches\Commands\{CreateComposerAuthCommand, CreateEnvironmentCommand, DeployEnvironmentCommand, DestroyEnvironmentCommand, InstallCommand, TestForgeConnectionCommand, UpdateDeployScriptCommand};
use Ddr\ForgeTestBranches\Services\{BranchPatternMatcher, BranchSanitizer, DeploymentScriptBuilder, DomainBuilder, EnvironmentBuilder};

class ForgeTestBranchesServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('forge-test-branches')
            ->hasConfigFile()
            ->hasCommands([
                InstallCommand::class,
                CreateEnvironmentCommand::class,
                DestroyEnvironmentCommand::class,
                DeployEnvironmentCommand::class,
                UpdateDeployScriptCommand::class,
                CreateComposerAuthCommand::class,
                TestForgeConnectionCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        $this->registerLogChannel();

        $this->app->singleton(ForgeClient::class, fn (): ForgeClient => new ForgeClient());
        $this->app->singleton(BranchPatternMatcher::class);
        $this->app->singleton(BranchSanitizer::class);
        $this->app->singleton(DomainBuilder::class);
        $this->app->singleton(DeploymentScriptBuilder::class);
        $this->app->singleton(EnvironmentBuilder::class);
        $this->app->singleton(ForgeTestBranches::class);
        $this->app->singleton(Logger::class);
    }

    public function packageBooted(): void
    {
        if (config('forge-test-branches.webhook.enabled')) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/webhook.php');
        }
    }

    private function registerLogChannel(): void
    {
        /** @var array<string, mixed> $loggingConfig */
        $loggingConfig = config('forge-test-branches.logging', []);
        $channelName = (string) ($loggingConfig['channel'] ?? 'forge-test-branches');

        $this->app->make('config')->set("logging.channels.{$channelName}", [
            'driver' => $loggingConfig['driver'] ?? 'daily',
            'path' => $loggingConfig['path'] ?? storage_path('logs/forge-test-branches.log'),
            'days' => $loggingConfig['days'] ?? 14,
            'level' => $loggingConfig['level'] ?? 'debug',
        ]);
    }
}
