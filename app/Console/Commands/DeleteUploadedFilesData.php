<?php

namespace App\Console\Commands;

use App\Models\UploadedFiles;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
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
        /** Selecting the file data row using delete column */
        $fileData = UploadedFiles::where('delete', 1)->first();
        $supplierTable = DB::table('supplier_tables')
        ->select('supplier_id', 'table_name')
        ->get();

        /** Selecting the file data row using table id in case of office depot weekly */
        if (!empty($fileData) && $fileData->supplier_id == 7) {
            $currentIdDetails = DB::table('odp_attachments')
            ->where('attachment_id', $fileData->id)
            ->first();

            $greater = DB::table('odp_attachments')
            ->where('attachment_id','>', $fileData->id)
            ->where('year', $currentIdDetails->year)
            ->first();

            DB::table('odp_attachments')
            ->where('attachment_id', $fileData->id)
            ->delete();

            if (!$greater && $currentIdDetails) {
                $fileIdForUpdate = DB::table('odp_attachments')
                ->where('attachment_id','<', $fileData->id)
                ->where('year', $currentIdDetails->year)
                ->orderBy('id', 'desc')
                ->limit(1)
                ->first();

                if ($fileIdForUpdate) {
                    UploadedFiles::where('id', $fileIdForUpdate->attachment_id)
                    ->update([
                        'cron' => 11, /** Updating the cron value to 11 old file ready for upload again */
                    ]);
                }
            }
        }

        /** Array of different supplires table */
        foreach ($supplierTable as $key => $value) {
            $supplierTableArray[$value->supplier_id] = $value->table_name;
        }

        if (isset($fileData->id) && !empty($fileData->id)) {
            /** Update delete two means start deleting data into excel */
            DB::table('attachments')->where('id', $fileData->id)->update(['delete' => UploadedFiles::CRON]);
            
            try {
                $id = $fileData->id;
                $fileData->delete = 0;
                $destinationPath = public_path('/excel_sheets');
                $filePath = $destinationPath.'/'.$fileData->file_name;
                $fileData->save();

                /** Delete records from UploadedFiles table */
                UploadedFiles::where('id', $id)->delete();
                
                /** Delete records from OrderDetails table */
                DB::table('order_details')->where('attachment_id', $id)->delete();
                DB::table('orders')->where('attachment_id', $id)->delete();

                if ($fileData->supplier_id == 4) {
                    /** Delete records from Suppliers Orders table */
                    DB::table('staples_order')
                    ->where('attachment_id', $id)
                    ->delete();

                    /** Delete records from Suppliers Orders table */
                    DB::table($supplierTableArray[$fileData->supplier_id])
                    ->where('attachment_id', $id)
                    ->delete();
                } else {
                    /** Delete records from Suppliers Orders table */
                    DB::table($supplierTableArray[$fileData->supplier_id])
                    ->where('attachment_id', $id)
                    ->delete();
                }    
                
                $supplierDataCount = DB::table('orders')
                ->select('id')
                ->where('supplier_id', $fileData->supplier_id)
                ->first();

                if (!$supplierDataCount) {
                    DB::table('customer_suppliers')
                    ->where('supplier_id', $fileData->supplier_id)
                    ->delete();

                    DB::table('master_account_detail')
                    ->where('supplier_id', $fileData->supplier_id)
                    ->delete();
                }

                if (File::exists($filePath)) {
                    try {
                        // unlink($fileToDelete);
                         File::delete($filePath);
                    } catch (\Exception $e) {
                        /** Log or handle the error */
                        $this->info('Error deleting file: ' . $e->getMessage());
                        Log::error('Error deleting file: ' . $e->getMessage());
                        
                        die;
                    }
                } else {
                    /** File does not exist */
                    $this->info('File does not exist at path: ' . $filePath);
                    Log::warning('File does not exist at path: ' . $filePath);
                    die;
                }

                /** Update the 'delete' field three after deleting done */
                DB::table('attachments')->where('id', $fileData->id)->update(['delete' => 0]);
            } catch (QueryException $e) {   
                $this->info('Database deletion failed:: ' . $e->getMessage());
                Log::error('Database deletion failed:: ' . $e->getMessage());
    
                die;
            }

            $this->info('File deleted successfully.');
        } else {
            $this->info('No file for delete.');
        }
    }
}
