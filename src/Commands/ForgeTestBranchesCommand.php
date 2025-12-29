<?php

namespace Ddr\ForgeTestBranches\Commands;

use Illuminate\Console\Command;

class ForgeTestBranchesCommand extends Command
{
    public $signature = 'forge-test-branches';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
