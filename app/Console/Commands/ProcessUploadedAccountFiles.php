<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ProcessUploadedAccountFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-uploaded-account-files';

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
        /** This is the folder path where we save the file */
        $destinationPath = public_path('/excel_sheets');
        $reader = new Xlsx(); /** Creating object of php excel library class */

        /** Loading excel file using path and name of file from table "uploaded_file" */
        $spreadSheet = $reader->load($destinationPath . '/' . 'accountinfo.xlsx', 2);    

        $workSheetArray = $spreadSheet->getSheet(0)->toArray();
        $count = 0;

        foreach ($workSheetArray as $key => $row) {
            if ($key == 0) {
                continue;
            }              

            $parent = DB::table('accounts')->where('customer_number', $row[8])->first();

            if (!empty($parent)) {
                $finalInsertArray[] = [
                    'parent_id' => $parent->parent_id,
                    'created_by' => 1,
                    'alies' => $row[1],
                    'record_type' => $row[3],
                    'account_name' => $row[2],
                    'volume_rebate' => $row[4],
                    'member_rebate' => $row[10],
                    'temp_end_date' => Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[12]))->format('Y-m-d H:i:s'),
                    'customer_number' => $row[0],
                    'temp_active_date' => Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[11]))->format('Y-m-d H:i:s'),
                    'category_supplier' => $row[5],
                    'sales_representative' => $row[6],
                    'cpg_customer_service_rep' => $row[7],
                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                ];
            } else {
                $finalInsertArray[] = [
                    'created_by' => 1,
                    'alies' => $row[1],
                    'record_type' => $row[3],
                    'account_name' => $row[2],
                    'volume_rebate' => $row[4],
                    'member_rebate' => $row[10],
                    'temp_end_date' => Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[12]))->format('Y-m-d H:i:s'),
                    'customer_number' => $row[0],
                    'temp_active_date' => Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[11]))->format('Y-m-d H:i:s'),
                    'category_supplier' => $row[5],
                    'sales_representative' => $row[6],
                    'cpg_customer_service_rep' => $row[7],
                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                ];  
            }

            if ($count == 100) {
                print_r($finalInsertArray);
                $count = 0;
                try {
                    DB::table('accounts_one')->insert($finalInsertArray);

                } catch (QueryException $e) {   
                    Log::error('Error in YourScheduledTask: ' . $e->getMessage());
                    echo "Database insertion failed: " . $e->getMessage();
                    die;
                }
                
                unset($finalInsertArray);
            }
            $count++; 
        }

        if (!empty($finalInsertArray)) {
            try {
                DB::table('accounts_one')->insert($finalInsertArray);
            } catch (QueryException $e) {   
                Log::error('Error in YourScheduledTask: ' . $e->getMessage());
                echo "Database insertion failed: " . $e->getMessage();
            }
        }

        unset($finalInsertArray);

    }
}
