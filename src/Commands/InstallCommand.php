<?php

namespace Stevebauman\Inventory\Commands;

use Illuminate\Console\Command;

/**
 * Class InstallCommand.
 * 
 * @codeCoverageIgnore
 */
class InstallCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'inventory:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs the inventory migrations';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $this->info('Checking Database Schema');

        $this->call('inventory:check-schema');

        $this->info('Running migrations');

        $this->call('inventory:run-migrations');

        $this->info('Inventory has been successfully installed');
    }
}
