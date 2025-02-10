<?php

namespace App\Console\Commands;

use phpseclib3\Net\SFTP;
use App\Mail\FileDownloaded;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
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
        $sftpRemotePath = '/files/';
        $localPath = storage_path('app/public/staples_data'); // Store in public

        $sftp = new SFTP($sftpHost);
        if (!$sftp->login($sftpUsername, $sftpPassword)) {
            $this->error('Failed to authenticate with SFTP server.');
            return;
        }

        $files = $sftp->nlist($sftpRemotePath);
        $downloadLinks = [];

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;

            $remoteFilePath = $sftpRemotePath . $file;
            $localFilePath = $localPath . DIRECTORY_SEPARATOR . $file;

            if ($sftp->get($remoteFilePath, $localFilePath)) {
                $this->info("Downloaded: $file");
                // $sftp->delete($remoteFilePath);
                print_r($file);
                // Generate a public URL
                $downloadLinks[] = asset("storage/staples_data/$file");
            } else {
                $this->error("Failed to download: $file");
            }
        }
        // $downloadLinks = [0=>"sadsd",1=>"sdfdsf"];
        // print_r($downloadLinks);
        try{
            if (!empty($downloadLinks)) {
                Mail::to('vishustaple.in@gmail.com')->send(new FileDownloaded($downloadLinks));
                $this->info('Download links emailed successfully.');
            }
        } catch (\Exception $e) {
            // Log any exceptions or errors that occur during the email send
            $this->error('Error sending email: ' . $e->getMessage());
        }

        $this->info('SFTP data retrieval completed.');
    }

}
