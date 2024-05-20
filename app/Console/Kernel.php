<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\{ProcessUploadedFiles, MailSend, DeleteUploadedFilesData, validateUploadedFile};

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.  
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command(MailSend::class)->everyMinute();
        $schedule->command(ProcessUploadedFiles::class)->everyFiveMinutes();
        $schedule->command(validateUploadedFile::class)->everyFourMinutes();
        $schedule->command(DeleteUploadedFilesData::class)->everyFiveMinutes();   
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
