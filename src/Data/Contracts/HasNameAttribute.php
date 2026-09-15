<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Data\Contracts;

interface HasNameAttribute
{
    public function getName(): string;
}
