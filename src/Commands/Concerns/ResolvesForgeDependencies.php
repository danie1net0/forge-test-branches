<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Commands\Concerns;

use Ddr\ForgeTestBranches\Exceptions\ConfigurationException;

/**
 * Resolves a package service from the container without letting a missing
 * FORGE_API_TOKEN/FORGE_ORGANIZATION crash the command with a raw stack
 * trace: type-hinting the service directly on handle() would let Laravel
 * throw ConfigurationException while resolving that parameter, before the
 * command body (and its own try/catch) ever runs.
 */
trait ResolvesForgeDependencies
{
    /**
     * @template TService of object
     *
     * @param class-string<TService> $class
     * @return TService|null
     */
    protected function resolveOrFail(string $class): ?object
    {
        try {
            return $this->laravel->make($class);
        } catch (ConfigurationException $configurationException) {
            // A plain line, not $this->error()'s boxed block: that block
            // hard-wraps at the terminal width, which can break a longer
            // message mid-word.
            $this->line("Configuration error: {$configurationException->getMessage()}");

            return null;
        }
    }
}
