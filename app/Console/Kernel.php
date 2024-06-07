<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\{
    ProcessUploadedFiles,
    validateUploadedFile,
    DeleteUploadedFilesData,
    ProcessDeleteCommissions,
    ProcessCommissionAndRebate,
    RemoveFrontZeroAccountNumber,
};

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.  
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command(ProcessDeleteCommissions::class)->weekends();
        $schedule->command(validateUploadedFile::class)->everyTenMinutes();
        $schedule->command(ProcessUploadedFiles::class)->everyFiveMinutes();
        $schedule->command(DeleteUploadedFilesData::class)->everyFiveMinutes();
        $schedule->command(RemoveFrontZeroAccountNumber::class)->everyTenMinutes();
        $schedule->command(ProcessCommissionAndRebate::class)->everyFifteenMinutes();
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
