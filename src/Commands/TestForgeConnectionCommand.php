<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Commands;

use Ddr\ForgeTestBranches\Integrations\Forge\ForgeClient;
use Illuminate\Console\Command;
use Throwable;

class TestForgeConnectionCommand extends Command
{
    protected $signature = 'forge-test-branches:test-connection';

    protected $description = 'Test Forge API connection and credentials';

    public function handle(ForgeClient $forge): int
    {
        $serverId = (int) config('forge-test-branches.server_id');
        $token = config('forge-test-branches.forge_api_token');
        $repository = config('forge-test-branches.git.repository');

        $this->info('Testing Forge API connection...');
        $this->newLine();

        $this->line("Server ID: <fg=yellow>{$serverId}</>");
        $this->line('Token: <fg=yellow>' . mb_substr((string) $token, 0, 10) . '...</>');
        $this->line("Repository: <fg=yellow>{$repository}</>");
        $this->newLine();

        try {
            $this->info('1. Testing server access...');
            $sites = $forge->sites()->list($serverId);
            $this->line('   <fg=green>✓</> Found ' . count($sites) . ' sites on server');
            $this->newLine();

            $this->info('2. Testing database access...');
            $databases = $forge->databases()->list($serverId);
            $this->line('   <fg=green>✓</> Found ' . count($databases) . ' databases');
            $this->newLine();

            $this->info('3. Testing database users access...');
            $users = $forge->databaseUsers()->list($serverId);
            $this->line('   <fg=green>✓</> Found ' . count($users) . ' database users');
            $this->newLine();

            $this->info('<fg=green>All tests passed! Forge API is accessible.</>');

            return self::SUCCESS;
        } catch (Throwable $throwable) {
            $this->error('Connection failed!');
            $this->newLine();
            $this->line("Error: <fg=red>{$throwable->getMessage()}</>");
            $this->newLine();

            if (str_contains($throwable->getMessage(), '404')) {
                $this->warn('Possible causes:');
                $this->line('  • Invalid FORGE_SERVER_ID');
                $this->line('  • Token does not have access to this server');
                $this->line('  • Server was deleted from Forge');
            }

            if (str_contains($throwable->getMessage(), '401')) {
                $this->warn('Possible causes:');
                $this->line('  • Invalid FORGE_API_TOKEN');
                $this->line('  • Token was revoked or expired');
            }

            return self::FAILURE;
        }
    }
}
