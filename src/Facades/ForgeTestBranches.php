<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Facades;

use Ddr\ForgeTestBranches\Data\EnvironmentData;
use Illuminate\Support\Facades\Facade;

/**
 * @method static EnvironmentData create(string $branch)
 * @method static void destroy(string $branch)
 * @method static void deploy(string $branch)
 * @method static EnvironmentData|null find(string $branch)
 * @method static bool exists(string $branch)
 *
 * @see \Ddr\ForgeTestBranches\ForgeTestBranches
 */
class ForgeTestBranches extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Ddr\ForgeTestBranches\ForgeTestBranches::class;
    }
}
