<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\{
    ReportGenrate,
    SendReportEmail,
    ProcessUploadedFiles,
    validateUploadedFile,
    DeleteUploadedFilesData,
    ProcessDeleteCommissions,
    ProcessCommissionAndRebate,
    RemoveFrontZeroAccountNumber,
    RetrieveStaplesDiversityData,
};

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.  
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command(ReportGenrate::class)->hourly()->withoutOverlapping();
        $schedule->command(SendReportEmail::class)->weeklyOn(1, '0:00')->withoutOverlapping();
        $schedule->command(ProcessDeleteCommissions::class)->weekends()->withoutOverlapping();
        $schedule->command(validateUploadedFile::class)->everyTenMinutes()->withoutOverlapping();
        $schedule->command(ProcessUploadedFiles::class)->everyFiveMinutes()->withoutOverlapping();
        $schedule->command(DeleteUploadedFilesData::class)->everyFiveMinutes()->withoutOverlapping();
        $schedule->command(RetrieveStaplesDiversityData::class)->everyFourHours()->withoutOverlapping();
        $schedule->command(RemoveFrontZeroAccountNumber::class)->everyTenMinutes()->withoutOverlapping();
        $schedule->command(ProcessCommissionAndRebate::class)->everyFifteenMinutes()->withoutOverlapping();
        // $schedule->command(RetrieveStaplesDiversityData::class)->timezone('America/New_York')->dailyAt('03:00')->withoutOverlapping();
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
