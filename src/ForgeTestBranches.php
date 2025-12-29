<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches;

use Ddr\ForgeTestBranches\Models\ReviewEnvironment;
use Ddr\ForgeTestBranches\Services\EnvironmentBuilder;

class ForgeTestBranches
{
    public function __construct(
        protected EnvironmentBuilder $builder
    ) {
    }

    public function create(string $branch): ReviewEnvironment
    {
        return $this->builder->create($branch);
    }

    public function destroy(string $branch): void
    {
        $environment = ReviewEnvironment::query()->where('branch', $branch)->firstOrFail();
        $this->builder->destroy($environment);
    }

    public function deploy(string $branch): void
    {
        $environment = ReviewEnvironment::query()->where('branch', $branch)->firstOrFail();
        $this->builder->deploy($environment);
    }

    public function find(string $branch): ?ReviewEnvironment
    {
        return ReviewEnvironment::query()->where('branch', $branch)->first();
    }

    public function exists(string $branch): bool
    {
        return ReviewEnvironment::query()->where('branch', $branch)->exists();
    }
}
