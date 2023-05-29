<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

use App\Jobs\update_topology_job;
use App\Commands\update_topology_command;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\update_topology_command::class,
    ];
    /**
     * Define the application's command schedule.
     */
    
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('topology:update')
        ->hourly()
        ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
