<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Facades;

use Ddr\ForgeTestBranches\Models\ReviewEnvironment;
use Illuminate\Support\Facades\Facade;

/**
 * @method static ReviewEnvironment create(string $branch)
 * @method static void destroy(string $branch)
 * @method static void deploy(string $branch)
 * @method static ReviewEnvironment|null find(string $branch)
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
