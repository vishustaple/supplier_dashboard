<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\ProcessUploadedFiles;

class Kernel extends ConsoleKernel
{

    protected $commands = [
        \App\Console\Commands\MailSend::class,
    ];
    /**
     * Define the application's command schedule.  
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        // $schedule->call(function () {
            
        // })->daily();

        // Schedule the ProcessUploadedFiles command to run daily
        $schedule->command(ProcessUploadedFiles::class)->daily();
        $schedule->command(MailSend::class)->everyMinute();
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
