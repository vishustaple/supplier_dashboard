<?php

namespace App\Console\Commands;

use phpseclib3\Net\SFTP;
use Illuminate\Console\Command;
// use Illuminate\Support\Facades\Storage;


class RetrieveStaplesDiversityData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:retrieve-staples-diversity-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $sftpHost = env('SFTP_HOST');
        $sftpUsername = env('SFTP_USERNAME');
        $sftpPassword = env('SFTP_PASSWORD');
        $sftpRemotePath = '/remote/path/';
        $localPath = storage_path('app/staples_data');

        $sftp = new SFTP($sftpHost);
        if (!$sftp->login($sftpUsername, $sftpPassword)) {
            $this->error('Failed to authenticate with SFTP server.');
            return;
        }

        $files = $sftp->nlist($sftpRemotePath);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;

            $remoteFilePath = $sftpRemotePath . $file;
            $localFilePath = $localPath . DIRECTORY_SEPARATOR . $file;

            if ($sftp->get($remoteFilePath, $localFilePath)) {
                $this->info("Downloaded: $file");
                $sftp->delete($remoteFilePath);
            } else {
                $this->error("Failed to download: $file");
            }
        }

        $this->info('SFTP data retrieval completed.');
    }
}
