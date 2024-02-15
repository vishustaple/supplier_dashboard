<?php

namespace App\Console\Commands;

use DB;
use App\Models\UploadedFiles;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;

class DeleteUploadedFilesData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:delete-uploaded-files-data';

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
        /** Selecting the file data row using table id */
        $fileData = UploadedFiles::where('delete', 1)->first();

        /** Update delete two means start deleting data into excel */
        DB::table('uploaded_files')->where('id', $fileData->id)
        ->update([
        'delete' => UploadedFiles::CRON
        ]);

        if (!empty($fileData->id)) {
            try {
                $id = $fileData->id;
                $fileData->delete = 0;
                $destinationPath = public_path('\excel_sheets');
    
                $filePath = $destinationPath .'\\'. $fileData->file_name;
                
                $fileData->save();
    
                /** Delete records from UploadedFiles table */
                UploadedFiles::where('id', $id)->delete();
    
                if (in_array($fileData->cron, [2, 3])) {
                    /** Delete records from ExcelData table */
                    DB::table('order_product_details')->where('data_id', $id)->delete();
        
                    /** Delete records from OrderDetails table */
                    DB::table('order_details')->where('data_id', $id)->delete();
        
                    /** Delete records from Order table */
                    DB::table('orders')->where('data_id', $id)->delete();
                }
    
    
                // if (Storage::exists($filePath)) {
                //     try {
                //         Storage::delete($filePath);
                //         // File deleted successfully
                //     } catch (\Exception $e) {
                //         // Log or handle the error
                //         Log::error('Error deleting file: ' . $e->getMessage());
                //         session()->flash('error', 'Error deleting file: ' . $e->getMessage());
                //     }
                // } else {
                //     // File does not exist
                //     Log::warning('File does not exist at path: ' . $filePath);
                //     session()->flash('error', 'File does not exist at path: ' . $filePath);
                // }
    
                /** Deleting uploded file from storage */
                // Storage::delete($fileData->file_name);

                /** Update the 'delete' field three after deleting done */
                DB::table('uploaded_files')->where('id', $fileData->id)
                ->update([
                'delete' => 0
                ]);
            } catch (QueryException $e) {   
                Log::error('Database deletion failed:: ' . $e->getMessage());
    
                /** Error message */
                session()->flash('error', $e->getMessage());
            }
        }
    }
}
