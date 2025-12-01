<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Check for stuck campaigns every 5 minutes and auto-fix them
        $schedule->command('campaigns:check-stuck --fix')
            ->everyFiveMinutes()
            ->withoutOverlapping(10) // Allow 10 minutes max execution time, release lock after 10 min
            ->appendOutputTo(storage_path('logs/stuck-campaigns-check.log'))
            ->onFailure(function () {
                \Illuminate\Support\Facades\Log::error('CheckStuckCampaigns scheduled task failed');
            });
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
