<?php

namespace App\Console\Commands;

use Excel;
use Illuminate\Console\Command;
use App\Imports\YourImportClass;
use Illuminate\Support\Facades\DB;

class ProcessUploadedFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-uploaded-files';

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
        /** this is the folder path where we save the file */
        $destinationPath = public_path('/excel_sheets');

        /** Select those file name where cron is one */
        $file_for_process = DB::table('uploaded_files')->select('supplier_id', 'file_name', 'file_path')->where('cron', '=', 1)->get();

        /** Inserting files data into the database after doing excel import */
        foreach ($file_for_process as $file_key => $file_value){
            Excel::import(new YourImportClass($file_value->supplier_id, $file_value->file_name, $destinationPath, true), $destinationPath . '/' . $file_value->file_name);
        }

        /** Optionally, update the 'cron' field after processing */
        DB::table('uploaded_files')
            ->where('cron', 1)
            ->update(['cron' => 0]);

        $this->info('Uploaded files processed successfully.');
    }
}
