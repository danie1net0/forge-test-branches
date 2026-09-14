<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Integrations\Forge\Requests\Concerns;

trait FiltersByName
{
    public function filterByName(string $name): static
    {
        $this->query()->add('filter[name]', $name);

        return $this;
    }
}
