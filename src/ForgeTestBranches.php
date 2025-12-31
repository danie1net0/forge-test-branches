<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches;

use Ddr\ForgeTestBranches\Data\EnvironmentData;
use Ddr\ForgeTestBranches\Services\EnvironmentBuilder;
use RuntimeException;

class ForgeTestBranches
{
    public function __construct(
        protected EnvironmentBuilder $builder
    ) {
    }

    public function create(string $branch): EnvironmentData
    {
        return $this->builder->create($branch);
    }

    public function destroy(string $branch): void
    {
        $environment = $this->builder->find($branch);

        if (! $environment instanceof EnvironmentData) {
            throw new RuntimeException("Environment not found for branch: {$branch}");
        }

        $this->builder->destroy($environment);
    }

    public function deploy(string $branch): void
    {
        $environment = $this->builder->find($branch);

        if (! $environment instanceof EnvironmentData) {
            throw new RuntimeException("Environment not found for branch: {$branch}");
        }

        $this->builder->deploy($environment);
    }

    public function find(string $branch): ?EnvironmentData
    {
        return $this->builder->find($branch);
    }

    public function exists(string $branch): bool
    {
        return $this->builder->exists($branch);
    }
}
