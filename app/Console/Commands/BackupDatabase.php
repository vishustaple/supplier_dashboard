<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backup-database';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle() {
        $filename = 'backup_' . Carbon::now()->format('Y_m_d_H_i_s') . '.sql';
        $path = storage_path("app/backups/{$filename}");

        $process = new Process([
            'mysqldump',
            '-u', env('DB_USERNAME'),
            '-p' . env('DB_PASSWORD'),
            env('DB_DATABASE'),
            '--result-file=' . $path,
        ]);

        $process->setTimeout(3600);

        try {
            $process->mustRun();
            $this->info('Database backup created successfully.');
            
            /** Optionally, download the backup to your local system */
            $this->downloadBackup($filename);
            
            /** Delete the backup file from the server */
            unlink($path);
            $this->info('Backup file deleted from the server.');

        } catch (ProcessFailedException $exception) {
            $this->error('The backup process has failed.');
        }
    }

    private function downloadBackup($filename) {
        $localPath = '/home/staple/Downloads/database_backups/' . $filename;
        $serverPath = storage_path("app/backups/{$filename}");

        /** Use SCP or SFTP to transfer the file to your local system */
        /** Example with scp (Secure Copy): */
        $process = new Process([
            'scp',
            '-o', 'StrictHostKeyChecking=no', // Automatically accept new host keys
            'rocky@3.95.106.180:' . $serverPath,
            $localPath
        ]);

        try {
            $process->mustRun();
            $this->info('Backup file downloaded successfully.');
        } catch (ProcessFailedException $exception) {
            $this->error('Failed to download the backup file.');
        }
    }
}
