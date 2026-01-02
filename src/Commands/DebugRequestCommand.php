<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Commands;

use Ddr\ForgeTestBranches\Data\CreateDatabaseData;
use Ddr\ForgeTestBranches\Integrations\Forge\ForgeConnector;
use Ddr\ForgeTestBranches\Integrations\Forge\Requests\Databases\CreateDatabaseRequest;
use Illuminate\Console\Command;
use ReflectionMethod;

class DebugRequestCommand extends Command
{
    protected $signature = 'forge-test-branches:debug';

    protected $description = 'Debug Forge API request';

    public function handle(): int
    {
        $serverId = (int) config('forge-test-branches.server_id');
        $token = config('forge-test-branches.api_token');

        $this->info("Server ID: {$serverId}");
        $this->info("Token: " . mb_substr((string) $token, 0, 20) . '...');

        $data = new CreateDatabaseData(name: 'debug_test_' . time());

        $this->info("\n=== CreateDatabaseData ===");
        $this->info("Constructor name: {$data->name}");
        $this->info("toArray(): " . json_encode($data->toArray()));

        $request = new CreateDatabaseRequest($serverId, $data);

        $this->info("\n=== Request defaultBody ===");
        $reflection = new ReflectionMethod($request, 'defaultBody');
        $this->info("defaultBody(): " . json_encode($reflection->invoke($request)));

        $this->info("\n=== Request body()->all() ===");
        $this->info("body()->all(): " . json_encode($request->body()->all()));

        $connector = new ForgeConnector($token);
        $pendingRequest = $connector->createPendingRequest($request);
        $psrRequest = $pendingRequest->createPsrRequest();

        $this->info("\n=== PSR Request ===");
        $this->info("Method: " . $psrRequest->getMethod());
        $this->info("URI: " . $psrRequest->getUri());

        $this->info("\nHeaders:");

        foreach ($psrRequest->getHeaders() as $name => $values) {
            if ($name === 'Authorization') {
                $this->info("  {$name}: Bearer " . mb_substr($values[0], 7, 20) . '...');
            } else {
                $this->info("  {$name}: " . implode(', ', $values));
            }
        }

        $psrRequest->getBody()->rewind();
        $body = $psrRequest->getBody()->getContents();
        $this->info("\nBody: {$body}");
        $this->info("Body length: " . mb_strlen($body));

        return self::SUCCESS;
    }
}
