<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use App\Models\{Account, Order, OrderDetails, UploadedFiles};


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
        $spreadSheet = $reader->load($destinationPath . '/' . 'master_account_detail.xlsx', 2);    

        
        $workSheetArray = $spreadSheet->getSheet(0)->toArray();

        foreach ($workSheetArray as $key => $row) {
            if ($key == 0) {
                continue;
            }

            $perent = Account::where('customer_number', $row[8])->first();
            $customer = Account::where('customer_number', $row[0])->first();
            $gdPerent = Account::where('customer_number', $row[10])->first();
            $supplier = DB::table('suppliers')->select('id')->where('supplier_name', $row[5])->first();

            if ($supplier) {
                $supplierId = $supplier->id;
            } else {
                $supplierId = DB::table('suppliers')->insertGetId(['supplier_name' => $row[5], 'show' => 1, 'created_by' => 1,'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => Carbon::now()->format('Y-m-d H:i:s')]);
            }

            if (empty($gdPerent) && empty($perent) && empty($customer)) {
                $lastInsertGdPerentId = Account::create([
                    'created_by' => 1,
                    'category_supplier' => $supplierId,
                    'customer_number' => $row[10],
                    'alies' => $row[11],
                    'parent_id' => null,
                    'record_type' => $row[3],
                    'account_name' => $row[2],
                    'volume_rebate' => $row[4],
                    'member_rebate' => $row[12],
                    'temp_end_date' => (isset($row[14]) && !empty($row[14])) ? (Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[14]))->format('Y-m-d H:i:s')) : (''),
                    'temp_active_date' => (isset($row[13]) && !empty($row[13])) ? (Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[13]))->format('Y-m-d H:i:s')) : (''),
                    'sales_representative' => $row[6],
                    'cpg_customer_service_rep' => $row[7],
                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                ]);

                $lastInsertPerentId = Account::create([
                    'created_by' => 1,
                    'category_supplier' => $supplierId,
                    'customer_number' => $row[8],
                    'alies' => $row[9],
                    'parent_id' => $lastInsertGdPerentId->id,
                    'record_type' => $row[3],
                    'account_name' => $row[2],
                    'volume_rebate' => $row[4],
                    'member_rebate' => $row[12],
                    'temp_end_date' => (isset($row[14]) && !empty($row[14])) ? (Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[14]))->format('Y-m-d H:i:s')) : (''),
                    'temp_active_date' => (isset($row[13]) && !empty($row[13])) ? (Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[13]))->format('Y-m-d H:i:s')) : (''),
                    'sales_representative' => $row[6],
                    'cpg_customer_service_rep' => $row[7],
                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                ]);

                Account::create([
                    'created_by' => 1,
                    'alies' => $row[1],
                    'record_type' => $row[3],
                    'account_name' => $row[2],
                    'volume_rebate' => $row[4],
                    'member_rebate' => $row[12],
                    'temp_end_date' => (isset($row[14]) && !empty($row[14])) ? (Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[14]))->format('Y-m-d H:i:s')) : (''),
                    'customer_number' => $row[0],
                    'temp_active_date' => (isset($row[13]) && !empty($row[13])) ? (Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[13]))->format('Y-m-d H:i:s')) : (''),
                    'category_supplier' => $supplierId,
                    'sales_representative' => $row[6],
                    'cpg_customer_service_rep' => $row[7],
                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    'parent_id' => $lastInsertPerentId->id,
                ]);

            } elseif (!empty($gdPerent) && empty($perent) && empty($customer)) {
                $lastInsertPerentId = Account::create([
                    'created_by' => 1,
                    'category_supplier' => $supplierId,
                    'customer_number' => $row[8],
                    'alies' => $row[9],
                    'parent_id' => $gdPerent->id,
                    'record_type' => $row[3],
                    'account_name' => $row[2],
                    'volume_rebate' => $row[4],
                    'member_rebate' => $row[12],
                    'temp_end_date' => (isset($row[14]) && !empty($row[14])) ? (Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[14]))->format('Y-m-d H:i:s')) : (''),
                    'temp_active_date' => (isset($row[13]) && !empty($row[13])) ? (Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[13]))->format('Y-m-d H:i:s')) : (''),
                    'sales_representative' => $row[6],
                    'cpg_customer_service_rep' => $row[7],
                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                ]);

                Account::create([
                    'created_by' => 1,
                    'alies' => $row[1],
                    'record_type' => $row[3],
                    'account_name' => $row[2],
                    'volume_rebate' => $row[4],
                    'member_rebate' => $row[12],
                    'temp_end_date' => (isset($row[14]) && !empty($row[14])) ? (Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[14]))->format('Y-m-d H:i:s')) : (''),
                    'customer_number' => $row[0],
                    'temp_active_date' => (isset($row[13]) && !empty($row[13])) ? (Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[13]))->format('Y-m-d H:i:s')) : (''),
                    'category_supplier' => $supplierId,
                    'sales_representative' => $row[6],
                    'cpg_customer_service_rep' => $row[7],
                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    'parent_id' => $lastInsertPerentId->id,
                ]);

            } elseif (!empty($gdPerent) && !empty($perent) && empty($customer)) {
                Account::create([
                    'created_by' => 1,
                    'alies' => $row[1],
                    'record_type' => $row[3],
                    'account_name' => $row[2],
                    'volume_rebate' => $row[4],
                    'member_rebate' => $row[12],
                    'temp_end_date' => (isset($row[14]) && !empty($row[14])) ? (Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[14]))->format('Y-m-d H:i:s')) : (''),
                    'customer_number' => $row[0],
                    'temp_active_date' => (isset($row[13]) && !empty($row[13])) ? (Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[13]))->format('Y-m-d H:i:s')) : (''),
                    'category_supplier' => $supplierId,
                    'sales_representative' => $row[6],
                    'cpg_customer_service_rep' => $row[7],
                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    'parent_id' => $perent->id,
                ]);
            } else {
                // echo "hello";
            }
        }
    }
}
