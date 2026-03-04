<?php

namespace Worldesports\MultiTenancy\Commands;

use Illuminate\Console\Command;

class MultiTenancyCommand extends Command
{
    public $signature = 'tenant:status';

    public $description = 'Show tenant and database status';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
