<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Commands;

use Ddr\ForgeTestBranches\Exceptions\ConfigurationException;
use Ddr\ForgeTestBranches\Integrations\Forge\ForgeClient;
use Illuminate\Console\Command;
use Saloon\Exceptions\Request\RequestException;
use Throwable;

class TestForgeConnectionCommand extends Command
{
    protected $signature = 'forge-test-branches:test-connection';

    protected $description = 'Test Forge API connection and credentials';

    public function handle(): int
    {
        $this->info('Testing Forge API connection...');
        $this->newLine();

        try {
            $forge = $this->laravel->make(ForgeClient::class);
        } catch (ConfigurationException $configurationException) {
            $this->error('Configuration error!');
            $this->line("  {$configurationException->getMessage()}");

            return self::FAILURE;
        }

        $this->printConfiguration();

        try {
            $this->testServerAccess($forge);
            $this->testDatabaseAccess($forge);
            $this->testDatabaseUserAccess($forge);

            $this->info('<fg=green>All tests passed! Forge API is accessible.</>');

            return self::SUCCESS;
        } catch (RequestException $exception) {
            $this->reportFailure($exception, $exception->getStatus());

            return self::FAILURE;
        } catch (Throwable $throwable) {
            $this->reportFailure($throwable, null);

            return self::FAILURE;
        }
    }

    protected function printConfiguration(): void
    {
        $organization = config('forge-test-branches.organization');
        $serverId = (int) config('forge-test-branches.server_id');
        $token = config('forge-test-branches.forge_api_token');
        $repository = config('forge-test-branches.git.repository');

        $this->line("Organization: <fg=yellow>{$organization}</>");
        $this->line("Server ID: <fg=yellow>{$serverId}</>");
        $this->line('Token: <fg=yellow>' . mb_substr((string) $token, 0, 10) . '...</>');
        $this->line("Repository: <fg=yellow>{$repository}</>");
        $this->newLine();
    }

    protected function testServerAccess(ForgeClient $forge): void
    {
        $serverId = (int) config('forge-test-branches.server_id');

        $this->info('1. Testing server access...');
        $sites = $forge->sites()->list($serverId);
        $this->line('   <fg=green>✓</> Found ' . count($sites) . ' sites on server');
        $this->newLine();
    }

    protected function testDatabaseAccess(ForgeClient $forge): void
    {
        $serverId = (int) config('forge-test-branches.server_id');

        $this->info('2. Testing database access...');
        $databases = $forge->databases()->list($serverId);
        $this->line('   <fg=green>✓</> Found ' . count($databases) . ' databases');
        $this->newLine();
    }

    protected function testDatabaseUserAccess(ForgeClient $forge): void
    {
        $serverId = (int) config('forge-test-branches.server_id');

        $this->info('3. Testing database users access...');
        $users = $forge->databaseUsers()->list($serverId);
        $this->line('   <fg=green>✓</> Found ' . count($users) . ' database users');
        $this->newLine();
    }

    protected function reportFailure(Throwable $throwable, ?int $status): void
    {
        $this->error('Connection failed!');
        $this->newLine();
        $this->line("Error: <fg=red>{$throwable->getMessage()}</>");
        $this->newLine();

        if ($status === 404) {
            $this->warn('Possible causes:');
            $this->line('  • Invalid FORGE_ORGANIZATION or FORGE_SERVER_ID');
            $this->line('  • Server does not belong to this organization');
            $this->line('  • Server was deleted from Forge');

            return;
        }

        if ($status === 401 || $status === 403) {
            $this->warn('Possible causes:');
            $this->line('  • Invalid FORGE_API_TOKEN');
            $this->line('  • Token was revoked or expired');
            $this->line('  • Token was created for the deprecated v1 API');
            $this->line('  • Token is missing required scopes');

            return;
        }

        if ($status === 429) {
            $this->warn('Possible causes:');
            $this->line('  • Rate limit exceeded (60 requests/minute per token)');
            $this->line('  • Wait a minute and try again');
        }
    }
}
