<?php

namespace Ddr\ForgeTestBranches\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Ddr\ForgeTestBranches\ForgeTestBranches
 */
class ForgeTestBranches extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Ddr\ForgeTestBranches\ForgeTestBranches::class;
    }
}
