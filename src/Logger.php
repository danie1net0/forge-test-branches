<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches;

use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

class Logger
{
    private readonly LoggerInterface $channel;

    private readonly bool $enabled;

    public function __construct()
    {
        $this->enabled = (bool) config('forge-test-branches.logging.enabled', true);
        $channelName = (string) config('forge-test-branches.logging.channel', 'forge-test-branches');
        $this->channel = Log::channel($channelName);
    }

    /** @param array<string, mixed> $context */
    public function info(string $message, array $context = []): void
    {
        if (! $this->enabled) {
            return;
        }

        $this->channel->info($message, $context);
    }

    /** @param array<string, mixed> $context */
    public function error(string $message, array $context = []): void
    {
        if (! $this->enabled) {
            return;
        }

        $this->channel->error($message, $context);
    }

    /** @param array<string, mixed> $context */
    public function debug(string $message, array $context = []): void
    {
        if (! $this->enabled) {
            return;
        }

        $this->channel->debug($message, $context);
    }

    /** @param array<string, mixed> $context */
    public function warning(string $message, array $context = []): void
    {
        if (! $this->enabled) {
            return;
        }

        $this->channel->warning($message, $context);
    }
}
