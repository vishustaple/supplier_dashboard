<?php

namespace App\Console\Commands;

use DB;
use App\Models\UploadedFiles;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Storage;

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
        // $nameArray = ['20240212110311_Grainger-CUPAA IDR - November 2023.xlsx', '20240212110336_WB-202312p1.xlsx', '20240212110419_SPLS-202311 SelectiveInsurance_1043890NYC_OrderDetail_Monthly_TET3655_TE6487_20231218.xlsx', '20240212114222_Grainger-CUPAA IDR - November 2023.xlsx', '20240212122056_Grainger-CUPAA IDR - November 2023.xlsx', '20240212122145_SPLS-202311 SelectiveInsurance_1043890NYC_OrderDetail_Monthly_TET3655_TE6487_20231218.xlsx', '20240212125424_Grainger-CUPAA IDR - November 2023.xlsx', '20240214092604_OD-CENTERPOINT USAGE 2023-NOV.xlsx', '20240214095337_Book1.xlsx', '20240214101754_od.xlsx', '20240214103457_staple_sample.xlsx', '20240214125537_wb_sample.xlsx', '20240215075329_wb_sample(2).xlsx', '20240215090832_OD-CENTERPOINT USAGE 2023-NOV.xlsx', '20240215120500_OD-CENTERPOINT USAGE 2023-NOV.xlsx', '20240215122422_OD-CENTERPOINT USAGE 2023-NOV.xlsx', '20240215130802_OD-CENTERPOINT USAGE 2023-NOV.xlsx'];

        // for ($i=0; $i < count($nameArray); $i++) { 
        //     $destinationPath = public_path('/excel_sheets');
        //     $filePath = $destinationPath.'/'.$nameArray[$i];
        //     if (File::exists($filePath)) {
        //         // unlink($fileToDelete);
        //         File::delete($filePath);
        //     } else {
        //         echo "file not exist";
        //     }
        // }

        // die;

        /** Selecting the file data row using table id */
        $fileData = UploadedFiles::where('delete', 1)->first();

        /** Update delete two means start deleting data into excel */
        DB::table('uploaded_files')->where('id', $fileData->id)->update(['delete' => UploadedFiles::CRON]);

        if (!empty($fileData->id)) {
            try {
                $id = $fileData->id;
                $fileData->delete = 0;
                $destinationPath = public_path('/excel_sheets');
                $filePath = $destinationPath.'/'.$fileData->file_name;
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
    
                if (File::exists($filePath)) {
                    try {
                        // unlink($fileToDelete);
                        File::delete($filePath);
                    } catch (\Exception $e) {
                        /** Log or handle the error */
                        Log::error('Error deleting file: ' . $e->getMessage());
                        session()->flash('error', 'Error deleting file: ' . $e->getMessage());
                    }
                } else {
                    /** File does not exist */
                    Log::warning('File does not exist at path: ' . $filePath);
                    session()->flash('error', 'File does not exist at path: ' . $filePath);
                }

                /** Update the 'delete' field three after deleting done */
                DB::table('uploaded_files')->where('id', $fileData->id)->update(['delete' => 0]);
            } catch (QueryException $e) {   
                Log::error('Database deletion failed:: ' . $e->getMessage());
    
                /** Error message */
                session()->flash('error', $e->getMessage());
            }
        }
    }
}
