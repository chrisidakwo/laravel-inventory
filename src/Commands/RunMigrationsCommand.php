<?php

namespace Stevebauman\Inventory\Commands;

use Illuminate\Console\Command;

/**
 * Class RunMigrationsCommand.
 * 
 * @codeCoverageIgnore
 */
class RunMigrationsCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'inventory:run-migrations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs the inventory migrations';

    /**
     * Execute the command.
     */
    public function handle(): void
    {
        $this->call('migrate', [
            '--path' => 'vendor/chrisidakwo/laravel-inventory/src/migrations',
        ]);
    }
}
