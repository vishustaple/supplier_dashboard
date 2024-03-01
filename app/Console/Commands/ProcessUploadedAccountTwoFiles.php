<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ProcessUploadedAccountTwoFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-uploaded-account-two-files';

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
        $spreadSheet = $reader->load($destinationPath . '/' . 'master_account_detail.xlsx', 2);    

        
        $workSheetArray = $spreadSheet->getSheet(0)->toArray();
        $count = 0;

        foreach ($workSheetArray as $key => $row) {
            if ($key == 0) {
                continue;
            }

            $supplier = DB::table('suppliers')->select('id')->where('supplier_name', $row[5])->first();

            if ($supplier) {
                $supplierId = $supplier->id;
            } else {
                $supplierId = DB::table('suppliers')->insertGetId(['supplier_name' => $row[5], 'show' => 1, 'created_by' => 1,'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => Carbon::now()->format('Y-m-d H:i:s')]);
            }
            
            $finalInsertArray[] = [
                'account_number' => $row[0],
                'customer_name' => $row[1],
                'account_name' => $row[2],
                'record_type' => $row[3],
                'volume_rebate' => $row[4],
                'category_supplier' => $supplierId,
                'cpg_sales_representative' => $row[6],
                'cpg_customer_service_rep' => $row[7],
                'parent_id' => $row[8],
                'parent_name' => $row[9],
                'grandparent_id' => $row[10],
                'grandparent_name' => $row[11],
                'member_rebate' => $row[12],
                'temp_active_date' => (isset($row[13]) && !empty($row[13])) ? (Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[13]))->format('Y-m-d H:i:s')) : (''),
                'temp_end_date' => (isset($row[14]) && !empty($row[14])) ? (Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[14]))->format('Y-m-d H:i:s')) : (''),
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ];
           
            if ($count == 100) {
                $count = 0;
                try {
                    DB::table('master_account_detail')->insert($finalInsertArray);
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
                DB::table('master_account_detail')->insert($finalInsertArray);
            } catch (QueryException $e) {   
                Log::error('Error in YourScheduledTask: ' . $e->getMessage());
                echo "Database insertion failed: " . $e->getMessage();
            }
        }

        unset($finalInsertArray);

    }
}
