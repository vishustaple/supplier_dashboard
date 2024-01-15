<?php

namespace App\Console\Commands;

use Illuminate\Support\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use App\Models\{Order, OrderProductDetail};

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
            $fileForProcess = DB::table('uploaded_files')->select('supplier_id', 'file_name')->where('cron', '=', 1)->get();
            
            if(!$fileForProcess->isEmpty()){
                /** Add column name here those row you want to skip */
                $skipRowArray = ["Shipto Location Total", "Shipto & Location Total", "TOTAL FOR ALL LOCATIONS", "Total"];

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
                        'invoice_no' => 'Invoice Date',
                        'invoice_date' => 'Invoice Num',
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
                        
                        $reader = new Xlsx(); /** Creating object of php excel library class */ 

                        /** Loading excel file using path and name of file from table "uploaded_file" */
                        $spreadSheet = $reader->load($destinationPath . '/' . $fileValue->file_name, 2);
                        
                        $sheetCount = $spreadSheet->getSheetCount(); /** Getting sheet count for run loop on index */
                        
                        if(in_array($fileValue->supplier_id, [3, 4])){
                            $sheetCount = ($sheetCount > 1) ? $sheetCount - 2 : $sheetCount; /** Handle case if sheet count is one */
                        }else{
                            $sheetCount = ($sheetCount > 1) ? $sheetCount - 1 : $sheetCount;
                        }
                        
                        // print_r($sheetCount);
                        
                        for ($i = 0; $i < $sheetCount; $i++) {
                            $count = $maxNonEmptyCount = 0;
                            
                            if($fileValue->supplier_id == 5 || $i==1){
                                continue;
                            }

                            $workSheetArray = $spreadSheet->getSheet($i)->toArray(); /** Getting worksheet using index */
                            
                            foreach ($workSheetArray as $key=>$values) {
                                /** Checking not empty columns */
                                $nonEmptyCount = count(array_filter(array_values($values), function ($item) {
                                    return !empty($item);
                                }));
                                
                                /** if column count is greater then previous row columns count. Then assigen value to '$maxNonEmptyvalue' */
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
                            unset($maxNonEmptyCount);
                            $valueCount = count($maxNonEmptyValue);
                            print_r($maxNonEmptyValue);

                            $startIndex = $startIndexValueArray; /** Specify the starting index for get the excel column value */
                            unset($startIndexValueArray);
                            foreach ($workSheetArray as $key => $row) {
                                if($key > $startIndex){
                                    $workSheetArray1[] = $row;
                                }
                            }
                            
                            /** For insert data into the database */
                            foreach ($workSheetArray1 as $key => $row) 
                            {
                                if (count(array_intersect($skipRowArray, $row)) <= 0) {
                                    foreach($row as $key1 => $value){
                                        if(!empty($maxNonEmptyValue[$key1])){
                                            $finalInsertArray[] = ['supplier_id' => $fileValue->supplier_id,
                                                'key' => $maxNonEmptyValue[$key1],
                                                'value' => $value,
                                                'file_name' => $fileValue->file_name,
                                                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
                                            ];   

                                            if(!empty($columnArray[$fileValue->supplier_id]['product_name']) && $columnArray[$fileValue->supplier_id]['product_name'] == $maxNonEmptyValue[$key1]){
                                                $orderProductDetailIdArray['product_name'] = $value;
                                            }
                                            if(!empty($columnArray[$fileValue->supplier_id]['product_brand']) && $columnArray[$fileValue->supplier_id]['product_brand'] == $maxNonEmptyValue[$key1]){
                                                $orderProductDetailIdArray['product_brand'] = $value;
                                            }
                                            if(!empty($columnArray[$fileValue->supplier_id]['product_description']) && $columnArray[$fileValue->supplier_id]['product_description'] == $maxNonEmptyValue[$key1]){
                                                $orderProductDetailIdArray['product_description'] = $value;
                                            }
                                            
                                            if(!empty($columnArray[$fileValue->supplier_id]['customer_number']) && $columnArray[$fileValue->supplier_id]['customer_number'] == $maxNonEmptyValue[$key1]){
                                                $finalOrderInsertArray['customer_number'] = $value;
                                            }
                                            if(!empty($columnArray[$fileValue->supplier_id]['product_sku']) && $columnArray[$fileValue->supplier_id]['product_sku'] == $maxNonEmptyValue[$key1]){
                                                $finalOrderInsertArray['product_sku'] = $value;
                                            }
                                            if(!empty($columnArray[$fileValue->supplier_id]['amount']) && $columnArray[$fileValue->supplier_id]['amount'] == $maxNonEmptyValue[$key1]){
                                                $finalOrderInsertArray['amount'] = $value;
                                            }
                                            if(!empty($columnArray[$fileValue->supplier_id]['invoice_no']) && $columnArray[$fileValue->supplier_id]['invoice_no'] == $maxNonEmptyValue[$key1]){
                                                $finalOrderInsertArray['invoice_no'] = $value;
                                            }
                                            if(!empty($columnArray[$fileValue->supplier_id]['invoice_date']) && $columnArray[$fileValue->supplier_id]['invoice_date'] == $maxNonEmptyValue[$key1]){
                                                $finalOrderInsertArray['invoice_date'] = $value;
                                            }
                                        }
                                    }
                                    try{
                                        $orderProductDetailIdArray['category_supplier_id'] = $fileValue->supplier_id;
                                            $orderProductDetailIdArray['record_type_id'] = 1;
                                     print_r($orderProductDetailIdArray);
                                            $orderProductDetailId = OrderProductDetail::create($orderProductDetailIdArray);
                                            $finalOrderInsertArray['product_details_id'] = $orderProductDetailId->id;
                                            $finalOrderInsertArray['category_supplier_id'] = $fileValue->supplier_id;
                                            $finalOrderInsertArray['record_type_id'] = 1;
                                            $finalOrderInsertArray['created_by'] = 1;
                                            $finalOrderInsertArray['created_at'] = Carbon::now()->format('Y-m-d H:i:s');
                                            $finalOrderInsertArray['updated_at'] = Carbon::now()->format('Y-m-d H:i:s');
                                            print_r($finalOrderInsertArray);
                                    } catch (QueryException $e) {   
                                        echo "Database order_product_detail table insertion failed: " . $e->getMessage();
                                    }
                                    // die("Hello");
                                    if($count == 5){
                                        $count = 0;
                                        try{
                                            DB::table('orders')->insert($finalOrderInsertArray);
                                            DB::table('excel_data')->insert($finalInsertArray);
                                        } catch (QueryException $e) {   
                                            echo "Database insertion failed: " . $e->getMessage();
                                        }
                                        
                                        unset($finalInsertArray, $finalOrderInsertArray);
                                    }

                                    $count++; 
                                }else{
                                    continue;
                                }
                            }

                            unset($workSheetArray1, $count, $maxNonEmptyValue);

                            if(!empty($finalInsertArray)){
                                try{
                                    DB::table('orders')->insert($finalOrderInsertArray);
                                    DB::table('excel_data')->insert($finalInsertArray);
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
                    // DB::table('uploaded_files')->where('cron', 1)->update(['cron' => 0]);

                    $this->info('Uploaded files processed successfully.');
                } catch (QueryException $e) {   
                    echo "Database updation failed: " . $e->getMessage();
                }
            }
        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
            echo "Error loading spreadsheet: " . $e->getMessage();
        } catch (QueryException $e) {   
            echo "Database table uploaded_files select query failed: " . $e->getMessage();
        }  
    }
}
