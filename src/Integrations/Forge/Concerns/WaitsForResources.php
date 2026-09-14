<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Integrations\Forge\Concerns;

use Closure;
use Ddr\ForgeTestBranches\Exceptions\{ResourceFailedException, ResourceTimeoutException};
use Illuminate\Support\Sleep;

trait WaitsForResources
{
    /**
     * @template TResource
     *
     * @param Closure(): TResource $fetchResource
     * @param Closure(TResource): bool $isReady
     * @param Closure(TResource): bool|null $hasFailed
     * @param Closure(TResource): string|null $describe
     * @return TResource
     */
    protected function waitUntil(
        Closure $fetchResource,
        Closure $isReady,
        int $maxAttempts,
        int $sleepSeconds,
        string $resourceLabel,
        ?Closure $hasFailed = null,
        ?Closure $describe = null,
    ): mixed {
        $lastResource = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $resource = $fetchResource();
            $lastResource = $resource;

            if ($isReady($resource)) {
                return $resource;
            }

            if ($hasFailed instanceof Closure && $hasFailed($resource)) {
                throw ResourceFailedException::withReason($resourceLabel, $this->describeResource($resource, $describe));
            }

            if ($attempt === $maxAttempts) {
                break;
            }

            Sleep::sleep($sleepSeconds);
        }

        throw ResourceTimeoutException::afterAttempts($resourceLabel, $maxAttempts, $this->describeResource($lastResource, $describe));
    }

    private function describeResource(mixed $resource, ?Closure $describe): string
    {
        if ($describe instanceof Closure) {
            return $describe($resource);
        }

        return 'unknown';
    }
}
