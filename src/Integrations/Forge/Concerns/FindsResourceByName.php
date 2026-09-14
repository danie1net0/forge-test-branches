<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Integrations\Forge\Concerns;

use Ddr\ForgeTestBranches\Data\Contracts\HasNameAttribute;

trait FindsResourceByName
{
    /**
     * @template TResource of HasNameAttribute
     *
     * @param array<int, TResource> $resources
     * @return TResource|null
     */
    protected function firstMatchingName(array $resources, string $name): ?HasNameAttribute
    {
        foreach ($resources as $resource) {
            if ($resource->getName() === $name) {
                return $resource;
            }
        }

        return null;
    }
}
