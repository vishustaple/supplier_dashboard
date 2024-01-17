<?php

namespace App\Console\Commands;

use Illuminate\Support\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use App\Models\{Account, Order, OrderDetails};

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
        /** This is the folder path where we save the file */
        $destinationPath = public_path('/excel_sheets');

        try{
            /** Select those file name where cron is one */
            $fileForProcess = DB::table('uploaded_files')->select('supplier_id', 'file_name', 'created_by')->where('cron', '=', 1)->get();
            
            if (!$fileForProcess->isEmpty()) {
                /** Add column name here those row you want to skip */
                $skipRowArray = ["Shipto Location Total", "Shipto & Location Total", "TOTAL FOR ALL LOCATIONS", "Total"];
                 
                /** This array for dynmically get column name for save data into tables */
                $columnArray = [ 
                    1 => [
                        'product_name' => 'PRODUCT',
                        'product_brand' => '',
                        'product_description' => 'DESCRIPTION',
                        'customer_number' => 'SOLD TOACCOUNT',
                        'product_sku' => '',
                        'amount' => 'ON-CORESPEND',
                        'invoice_no' => '',
                        'invoice_date' => '',   
                    ],
                    2 => [
                        'product_name' => 'Material',
                        'product_brand' => 'Brand Name',
                        'product_description' => 'Material Description',
                        'customer_number' => 'Account Number',
                        'product_sku' => '',
                        'amount' => 'Actual Price Paid',
                        'invoice_no' => 'Invoice Number',
                        'invoice_date' => 'Bill Date',
                    ],
                    3 => [
                        'product_name' => 'Manufacture Item#',
                        'product_brand' => 'Manufacture Name',
                        'product_description' => 'Product Description',
                        'customer_number' => 'CUSTOMER ID',
                        'product_sku' => 'SKU',
                        'amount' => 'Total Spend',
                        'invoice_no' => 'Invoice #',
                        'invoice_date' => 'Shipped Date',
                    ],
                    4 => [
                        'product_name' => 'ITEMDESCRIPTION',
                        'product_brand' => 'STAPLESOWNBRAND',
                        'product_description' => 'STAPLESADVANTAGEITEMDESCRIPTION',
                        'customer_number' => 'MASTER_CUSTOMER',
                        'product_sku' => 'SKUNUMBER',
                        'amount' => 'ADJGROSSSALES',
                        'invoice_no' => 'INVOICENUMBER',
                        'invoice_date' => 'INVOICEDATE',
                    ],
                    5 => [
                        'product_name' => 'Item Name',
                        'product_brand' => '',
                        'product_description' => '',
                        'customer_number' => 'Customer Num',
                        'product_sku' => 'Item Num',
                        'amount' => 'Current List',
                        'invoice_no' => 'Invoice Num',
                        'invoice_date' => 'Invoice Date',
                    ],
                    6 => [
                        'product_name' => 'Material',
                        'product_brand' => 'Ownbrand',
                        'product_description' => 'Material Description',
                        'customer_number' => 'Leader customer 1',
                        'product_sku' => 'Qty. in SKU',
                        'amount' => 'Sales Amount - P',
                        'invoice_no' => 'Invoice list',
                        'invoice_date' => 'Billing Date',
                    ],
                ];

                try {
                    /** Increasing the memory limit becouse memory limit issue */
                    ini_set('memory_limit', '1024M');

                    /** Inserting files data into the database after doing excel import */
                    foreach ($fileForProcess as $fileKey => $fileValue){    
                        unset($spreadSheet, $reader);
                        // print_r($fileValue->created_by);die;
                        $reader = new Xlsx(); /** Creating object of php excel library class */ 

                        /** Loading excel file using path and name of file from table "uploaded_file" */
                        $spreadSheet = $reader->load($destinationPath . '/' . $fileValue->file_name, 2);
                        
                        $sheetCount = $spreadSheet->getSheetCount(); /** Getting sheet count for run loop on index */
                        
                        if ($fileValue->supplier_id == 4) {
                            $sheetCount = ($sheetCount > 1) ? $sheetCount - 2 : $sheetCount; /** Handle case if sheet count is one */
                        } else if ($fileValue->supplier_id == 3) {
                            $sheetCount = ($sheetCount > 1) ? $sheetCount - 1 : $sheetCount; /** Handle case if sheet count is one */
                        } else {
                            $sheetCount = ($sheetCount > 1) ? $sheetCount - 1 : $sheetCount;
                        }
                        
                        // print_r($sheetCount);
                        // die;

                        for ($i = 0; $i <= $sheetCount; $i++) {
                            $count = $maxNonEmptyCount = 0;

                            // print_r($i);
                            
                            if($fileValue->supplier_id == 4 && $i == 1){
                                continue;
                            }

                            if($fileValue->supplier_id == 5 && $i == 0){
                                continue;
                            }

                            $workSheetArray = $spreadSheet->getSheet($i)->toArray(); /** Getting worksheet using index */
                            foreach ($workSheetArray as $key=>$values) {
                                /** Checking not empty columns */
                                $nonEmptyCount = count(array_filter(array_values($values), function ($item) {
                                    return !empty($item);
                                }));
                                
                                /** If column count is greater then previous row columns count. Then assigen value to '$maxNonEmptyvalue' */
                                if ($nonEmptyCount > $maxNonEmptyCount) {
                                    $maxNonEmptyValue = $values;
                                    $startIndexValueArray = $key;
                                    $maxNonEmptyCount = $nonEmptyCount;
                                } 
                                
                                /** Stop loop after reading 31 rows from excel file */
                                if($key > 20){
                                    break;
                                }
                            }
                            
                            // print_r($maxNonEmptyValue);
                            // die;

                            /** Unset the "$maxNonEmptyCount" for memory save */
                            unset($maxNonEmptyCount);

                            $startIndex = $startIndexValueArray; /** Specify the starting index for get the excel column value */

                            /** Unset the "$startIndexValueArray" for memory save */
                            unset($startIndexValueArray);

                            if ($fileValue->supplier_id == 2) {
                               $graingerCount = $startIndex + 1;
                            }

                            foreach ($workSheetArray as $key => $row) {
                                if($key > $startIndex){
                                    $workSheetArray1[] = $row;

                                    if($fileValue->supplier_id == 3){
                                        $perent = Account::where('customer_number', $row[2])->first();
                                        $gdPerent = Account::where('customer_number', $row[0])->first();
                                        $customer = Account::where('customer_number', $row[4])->first();

                                        if (empty($gdPerent) && empty($perent) && empty($customer)) {
                                            $lastInsertGdPerentId = Account::create(['customer_number' => $row[0], 'customer_name' => $row[1], 'parent_id' => null, 'created_by' => $fileValue->created_by]);

                                            $lastInsertPerentId = Account::create(['customer_number' => $row[2], 'customer_name' => $row[3], 'parent_id' => $lastInsertGdPerentId->id, 'created_by' => $fileValue->created_by]);

                                            Account::create(['customer_number' => $row[4], 'customer_name' => $row[5], 'parent_id' => $lastInsertPerentId->id, 'created_by' => $fileValue->created_by]);

                                        } elseif (!empty($gdPerent) && empty($perent) && empty($customer)) {
                                            $lastInsertPerentId = Account::create(['customer_number' => $row[2], 'customer_name' => $row[3], 'parent_id' => $gdPerent->id, 'created_by' => $fileValue->created_by]);

                                            Account::create(['customer_number' => $row[4], 'customer_name' => $row[5], 'parent_id' => $lastInsertPerentId->id, 'created_by' => $fileValue->created_by]);

                                        } elseif (!empty($gdPerent) && !empty($perent) && empty($customer)) {
                                            Account::create(['customer_number' => $row[4], 'customer_name' => $row[5], 'parent_id' => $perent->id, 'created_by' => $fileValue->created_by]);

                                        } else {
                                            // echo "hello";
                                        }
                                    }
                                    
                                    if($fileValue->supplier_id == 4){
                                        $perent = Account::where('customer_number', $row[2])->first();
                                        $gdPerent = Account::where('customer_number', $row[0])->first();

                                        if (empty($gdPerent) && empty($perent)) {
                                            $lastInsertGdPerentId = Account::create(['customer_number' => $row[0], 'customer_name' => $row[1], 'parent_id' => null, 'created_by' => $fileValue->created_by]);

                                            $lastInsertPerentId = Account::create(['customer_number' => $row[2], 'customer_name' => $row[3], 'parent_id' => $lastInsertGdPerentId->id, 'created_by' => $fileValue->created_by]);

                                        } elseif (!empty($gdPerent) && empty($perent)) {
                                            $lastInsertPerentId = Account::create(['customer_number' => $row[2], 'customer_name' => $row[3], 'parent_id' => $gdPerent->id, 'created_by' => $fileValue->created_by]);

                                        } else {
                                            // echo "hello";
                                        }
                                    }

                                    if ($fileValue->supplier_id == 2) {
                                        if ($key > $graingerCount) {
                                            $perent = Account::where('customer_number', $row[2])->first();
                                            $gdPerent = Account::where('customer_number', $row[0])->first();
                                            $customer = Account::where('customer_number', $row[4])->first();
                                        
                                            if (empty($gdPerent) && empty($perent) && empty($customer)) {
                                                $lastInsertGdPerentId = Account::create(['customer_number' => $row[0], 'customer_name' => $row[1], 'parent_id' => null, 'created_by' => $fileValue->created_by]);
                                                $lastInsertPerentId = Account::create(['customer_number' => $row[2], 'customer_name' => $row[3], 'parent_id' => $lastInsertGdPerentId->id, 'created_by' => $fileValue->created_by]);

                                                Account::create(['customer_number' => $row[4], 'customer_name' => $row[5], 'parent_id' => $lastInsertPerentId->id, 'created_by' => $fileValue->created_by]);

                                            } elseif (!empty($gdPerent) && empty($perent) && empty($customer)) {
                                                $lastInsertPerentId = Account::create(['customer_number' => $row[2], 'customer_name' => $row[3], 'parent_id' => $gdPerent->id, 'created_by' => $fileValue->created_by]);

                                                Account::create(['customer_number' => $row[4], 'customer_name' => $row[5], 'parent_id' => $lastInsertPerentId->id, 'created_by' => $fileValue->created_by]);

                                            } elseif (!empty($gdPerent) && !empty($perent) && empty($customer)) {
                                                Account::create(['customer_number' => $row[4], 'customer_name' => $row[5], 'parent_id' => $perent->id, 'created_by' => $fileValue->created_by]);

                                            } else {
                                                // echo "hello";
                                            }
                                        }
                                    }

                                    if ($fileValue->supplier_id == 5) {
                                        $perent = Account::where('customer_number', $row[1])->first();
                                        if (empty($perent)) {
                                            Account::create(['customer_number' => $row[1], 'customer_name' => $row[2], 'parent_id' => null, 'created_by' => $fileValue->created_by]);
                                        }
                                    }
                                }
                            }

                            /** For insert data into the database */
                            foreach ($workSheetArray1 as $key => $row){
                                if (count(array_intersect($skipRowArray, $row)) <= 0) {
                                    foreach($row as $key1 => $value){
                                        if(!empty($maxNonEmptyValue[$key1])){

                                            if($fileValue->supplier_id == 3){
                                                $finalInsertArray[] = [
                                                    'invoice_number' => $row[25],
                                                    'key' => $maxNonEmptyValue[$key1],
                                                    'value' => $value,
                                                    'file_name' => $fileValue->file_name,
                                                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                    'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                ];  
                                            }

                                            if($fileValue->supplier_id == 5){
                                                $finalInsertArray[] = [
                                                    'invoice_number' => $row[3],
                                                    'key' => $maxNonEmptyValue[$key1],
                                                    'value' => $value,
                                                    'file_name' => $fileValue->file_name,
                                                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                    'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                ]; 
                                            }

                                            if($fileValue->supplier_id == 4){
                                                $finalInsertArray[] = [
                                                    'invoice_number' => $row[30],
                                                    'key' => $maxNonEmptyValue[$key1],
                                                    'value' => $value,
                                                    'file_name' => $fileValue->file_name,
                                                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                    'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                ]; 
                                            }

                                            if($fileValue->supplier_id == 2){
                                                $finalInsertArray[] = [
                                                    'invoice_number' => $row[29],
                                                    'key' => $maxNonEmptyValue[$key1],
                                                    'value' => $value,
                                                    'file_name' => $fileValue->file_name,
                                                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                    'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                ]; 
                                            }

                                            if(!empty($columnArray[$fileValue->supplier_id]['customer_number']) && $columnArray[$fileValue->supplier_id]['customer_number'] == $maxNonEmptyValue[$key1]){
                                                $finalOrderInsertArray['customer_number'] = $value;
                                            } else {
                                                $finalOrderInsertArray['customer_number'] = '';
                                            }

                                            if(!empty($columnArray[$fileValue->supplier_id]['amount']) && $columnArray[$fileValue->supplier_id]['amount'] == $maxNonEmptyValue[$key1]){
                                                $finalOrderInsertArray['amount'] = $value;
                                            } else {
                                                $finalOrderInsertArray['amount'] = '';
                                            }

                                            if(!empty($columnArray[$fileValue->supplier_id]['invoice_no']) && $columnArray[$fileValue->supplier_id]['invoice_no'] == $maxNonEmptyValue[$key1]){
                                                if (empty($value)) {
                                                    $finalOrderInsertArray['invoice_no'] = 1;
                                                } else {
                                                    $finalOrderInsertArray['invoice_no'] = $value;
                                                }
                                            } else {
                                                $finalOrderInsertArray['invoice_no'] = '';
                                            }

                                            if(!empty($columnArray[$fileValue->supplier_id]['invoice_date']) && $columnArray[$fileValue->supplier_id]['invoice_date'] == $maxNonEmptyValue[$key1]){
                                                if (empty($value)) {
                                                    $finalOrderInsertArray['invoice_date'] = Carbon::now()->format('Y-m-d H:i:s');
                                                } else {
                                                    if ($fileValue->supplier_id == 4) {
                                                        $finalOrderInsertArray['invoice_date'] = Carbon::createFromFormat('Y-m-d H:i:s', $value);
                                                    } else {
                                                        $finalOrderInsertArray['invoice_date'] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value))->format('Y-m-d H:i:s');
                                                    }
                                                }  
                                            } else {
                                                $finalOrderInsertArray['invoice_date'] = '';
                                            }
                                        }
                                    }

                                    $order = Order::where('invoice_no', $finalOrderInsertArray['invoice_no'])->where('invoice_date', $finalOrderInsertArray['invoice_date'])->first();
                                    if (isset($finalOrderInsertArray['invoice_no']) && empty($finalOrderInsertArray['invoice_no'])) {
                                        $systemCreatedInvoice = Order::random_invoice_num();
                                        Order::create([
                                            'invoice_no' => $systemCreatedInvoice,
                                            'invoice_date' => $fileValue->start_date,
                                            'customer_number' => $finalOrderInsertArray['customer_number'],
                                            'created_by' => $fileValue->created_by,
                                            'supplier_id' => $fileValue->supplier_id,
                                            'amount' => $finalOrderInsertArray['amount'],
                                            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                            'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                        ]);

                                        OrderDetails::create([
                                            'invoice_number' => $systemCreatedInvoice,
                                            'invoice_date' => $fileValue->start_date,
                                            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                            'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                        ]);
                                    } else {    
                                        if ($order) {
                                            $order->update([
                                                'created_by' => $fileValue->created_by,
                                                'amount' => $order->amount + $finalOrderInsertArray['amount'],
                                                'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                            ]);
                                        } else {
                                            Order::create([
                                                'invoice_no' => $finalOrderInsertArray['invoice_no'],
                                                'invoice_date' => $finalOrderInsertArray['invoice_date'],
                                                'customer_number' => $finalOrderInsertArray['customer_number'],
                                                'created_by' => $fileValue->created_by,
                                                'supplier_id' => $fileValue->supplier_id,
                                                'amount' => $finalOrderInsertArray['amount'],
                                                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                            ]);
                                        }
                                    }
                                    
                                    if($count == 5){
                                        $count = 0;
                                        try{
                                            DB::table('order_product_details')->insert($finalInsertArray);
                                        } catch (QueryException $e) {   
                                            echo "Database insertion failed: " . $e->getMessage();
                                        }
                                        
                                        unset($finalInsertArray);
                                    }

                                    $count++; 
                                }else{
                                    continue;
                                }
                            }

                            unset($workSheetArray1, $count, $maxNonEmptyValue);

                            if(!empty($finalInsertArray)){
                                try{
                                    DB::table('order_product_details')->insert($finalInsertArray);
                                } catch (QueryException $e) {   
                                    echo "Database insertion failed: " . $e->getMessage();
                                }
                            }

                            unset($finalInsertArray, $finalOrderInsertArray);
                        }
                    }
                } catch (\Exception $e) {
                    echo "Error loading spreadsheet: " . $e->getMessage();
                }

                try{
                    /** Optionally, update the 'cron' field after processing */
                    DB::table('uploaded_files')->where('cron', 1)->update(['cron' => 0]);

                    $this->info('Uploaded files processed successfully.');
                } catch (QueryException $e) {   
                    echo "Database updation failed: " . $e->getMessage();
                }
            } else {
                echo "No file left to upload.";
            }
        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
            echo "Error loading spreadsheet: " . $e->getMessage();
        } catch (QueryException $e) {   
            echo "Database table uploaded_files select query failed: " . $e->getMessage();
        }  
    }
}
