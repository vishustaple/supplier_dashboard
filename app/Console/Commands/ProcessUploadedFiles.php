<?php

namespace App\Console\Commands;

use Illuminate\Support\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use App\Models\{Account, Order, OrderDetails, UploadedFiles};

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
            $fileValue = DB::table('uploaded_files')->select('id', 'supplier_id', 'file_name', 'start_date', 'end_date', 'created_by')->where('cron', '=', UploadedFiles::UPLOAD)->whereNull('deleted_by')->first();

            // $monthsDifference = $interval->m;
            // $yearsDifference = $interval->y;
            
            if ($fileValue !== null) {
                /** Update cron two means start processing data into excel */
                DB::table('uploaded_files')->where('id', $fileValue->id)
                ->update([
                'cron' => UploadedFiles::CRON
                ]);

                /** Add column name here those row you want to skip */
                $skipRowArray = ["Shipto Location Total", "Shipto & Location Total", "TOTAL FOR ALL LOCATIONS", "Total"];
                 
                $columnValues = DB::table('manage_columns')->select('id', 'supplier_id', 'field_name')->where('supplier_id', $fileValue->supplier_id)->get();

                foreach ($columnValues as $key => $value) {
                    if (in_array($value->id, [14, 44, 19])) {
                        $columnArray[$value->supplier_id]['gd_customer_number'] =  $value->field_name;
                    }

                    if (in_array($value->id, [15, 45, 200])) {
                        $columnArray[$value->supplier_id]['gd_customer_name'] = $value->field_name;
                    }

                    if (in_array($value->id, [16, 46, 201])) {
                        $columnArray[$value->supplier_id]['p_customer_number'] = $value->field_name;
                    }

                    if (in_array($value->id, [17, 47, 202])) {
                        $columnArray[$value->supplier_id]['p_customer_name'] = $value->field_name;
                    }

                    if (in_array($value->id, [2, 18, 49, 72, 126, 148, 204])) {
                        $columnArray[$value->supplier_id]['customer_name'] = $value->field_name;
                    }

                    if (in_array($value->id, [1, 19, 48, 71, 125, 149, 203])) {
                        $columnArray[$value->supplier_id]['customer_number'] = $value->field_name;
                    }

                    if ($value->supplier_id == 7) {
                        $columnArray[$value->supplier_id]['amount'] = '';
                    }

                    if (in_array($value->id, [12, 38, 65, 122, 143, 185])) {
                        $columnArray[$value->supplier_id]['amount'] = $value->field_name;
                    }

                    if (in_array($value->supplier_id, [1, 7])) {
                        $columnArray[$value->supplier_id]['invoice_no'] = '';
                    }

                    if (in_array($value->id, [43, 69, 101, 127, 194])) {
                        $columnArray[$value->supplier_id]['invoice_no'] = $value->field_name;
                    }

                    if (in_array($value->id, [24, 68, 103, 128, 195])) {
                        $columnArray[$value->supplier_id]['invoice_date'] = $value->field_name;
                    }

                    if (in_array($value->supplier_id, [1,7])) {
                        $columnArray[$value->supplier_id]['invoice_date'] = '';
                    }

                    if ($value->supplier_id == 1) {
                        if ($value->id == 13) {
                            $offCoreSpend = $value->field_name;
                        }
                    }
                }

                try {
                    /** Increasing the memory limit becouse memory limit issue */
                    ini_set('memory_limit', '1024M');

                    /** Inserting files data into the database after doing excel import */
                        $weeklyCheck = true;
                       
                        unset($spreadSheet, $reader);
                        // print_r($fileValue->created_by);die;
                        $reader = new Xlsx(); /** Creating object of php excel library class */ 

                        /** Loading excel file using path and name of file from table "uploaded_file" */
                        $spreadSheet = $reader->load($destinationPath . '/' . $fileValue->file_name, 2);
                        
                        $sheetCount = $spreadSheet->getSheetCount(); /** Getting sheet count for run loop on index */
                        
                        if ($fileValue->supplier_id == 4 || $fileValue->supplier_id == 3) {
                            $sheetCount = ($sheetCount > 1) ? $sheetCount - 2 : $sheetCount; /** Handle case if sheet count is one */
                        } else {
                            $sheetCount = ($sheetCount > 1) ? $sheetCount - 1 : $sheetCount;
                        }
                        
                        // print_r($sheetCount);
                        // die;

                        $supplierFilesNamesArray = [
                            1 => 'Usage By Location and Item',
                            2 => 'Invoice Detail Report',
                            // 3 => '',
                            4 => 'All Shipped Order Detail',
                            5 => 'Centerpoint_Summary_Report',
                            6 => 'Blad1',
                            7 => 'Weekly Sales Account Summary', 
                        ];

                        // print_r($sheetCount);die;
                        for ($i = 0; $i <= $sheetCount; $i++) {
                            $count = $maxNonEmptyCount = 0;

                            // print_r($i);
                            
                            if (($sheetCount == 1 && $i == 1 && $fileValue->supplier_id != 5) || ($fileValue->supplier_id == 5 && $i == 0) || ($fileValue->supplier_id == 7 && in_array($i, [0, 1, 3, 4, 5, 6, 7]))) {
                                continue;
                            }

                            if ($fileValue->supplier_id != 3) {
                                $sheet = $spreadSheet->getSheetByName($supplierFilesNamesArray[$fileValue->supplier_id]);
                            }
                
                            if (isset($sheet) && $sheet) {
                                $workSheetArray = $sheet->toArray();
                            } else {
                                $workSheetArray = $spreadSheet->getSheet($i)->toArray(); /** Getting worksheet using index */
                            }
                            

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
                                if ($key > 20) {
                                    break;
                                }
                            }

                            /** Clean up the values */
                            $maxNonEmptyValue = array_map(function ($value) {
                                /** Remove line breaks and trim whitespace */
                                return str_replace(["\r", "\n"], '', $value);
                            }, $maxNonEmptyValue);

                            // print_r($maxNonEmptyValue);
                            // die;

                            if ($fileValue->supplier_id == 7) {
                                $weeklyPriceColumnArray = [];
                                foreach ($maxNonEmptyValue as $key => $value) {
                                    if ($key >= 6) {
                                        $weeklyPriceColumnArray[$key] = $value;
                                        // $weeklyArrayKey++;
                                    }
                                }
                            }

                            // print_r($weeklyPriceColumnArray);
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
                                    if (!empty($columnArray[$fileValue->supplier_id]['gd_customer_number'])) {
                                        $keyGrandParent = array_search($columnArray[$fileValue->supplier_id]['gd_customer_number'], $maxNonEmptyValue);
                                    }

                                    if (!empty($columnArray[$fileValue->supplier_id]['p_customer_number'])) {
                                        $keyParent = array_search($columnArray[$fileValue->supplier_id]['p_customer_number'], $maxNonEmptyValue);
                                    }

                                    if (!empty($columnArray[$fileValue->supplier_id]['customer_number'])) {
                                        $keyCustomer = array_search($columnArray[$fileValue->supplier_id]['customer_number'], $maxNonEmptyValue);
                                    }


                                    if (!empty($columnArray[$fileValue->supplier_id]['gd_customer_name'])) {
                                        $keyGrandParentName = array_search($columnArray[$fileValue->supplier_id]['gd_customer_name'], $maxNonEmptyValue);
                                    }

                                    if (!empty($columnArray[$fileValue->supplier_id]['p_customer_name'])) {
                                        $keyParentName = array_search($columnArray[$fileValue->supplier_id]['p_customer_name'], $maxNonEmptyValue);
                                    }

                                    if (!empty($columnArray[$fileValue->supplier_id]['customer_name'])) {
                                        $keyCustomerName = array_search($columnArray[$fileValue->supplier_id]['customer_name'], $maxNonEmptyValue);
                                    }

                                    if (($fileValue->supplier_id == 2 && $key > $graingerCount) || $fileValue->supplier_id == 3 || $fileValue->supplier_id == 7) {
                                        $gdPerent = Account::where('customer_number', $row[$keyGrandParent])->first();
                                        $perent = Account::where('customer_number', $row[$keyParent])->first();
                                        $customer = Account::where('customer_number', $row[$keyCustomer])->first();

                                        if (empty($gdPerent) && empty($perent) && empty($customer)) {
                                            $lastInsertGdPerentId = Account::create(['category_supplier' => $fileValue->supplier_id, 'customer_number' => $row[$keyGrandParent], 'alies' => $row[$keyGrandParentName], 'parent_id' => null, 'created_by' => $fileValue->created_by]);

                                            $lastInsertPerentId = Account::create(['category_supplier' => $fileValue->supplier_id, 'customer_number' => $row[$keyParent], 'alies' => $row[$keyParentName], 'parent_id' => $lastInsertGdPerentId->id, 'created_by' => $fileValue->created_by]);

                                            Account::create(['category_supplier' => $fileValue->supplier_id, 'customer_number' => $row[$keyCustomer], 'alies' => $row[$keyCustomerName], 'parent_id' => $lastInsertPerentId->id, 'created_by' => $fileValue->created_by]);

                                        } elseif (!empty($gdPerent) && empty($perent) && empty($customer)) {
                                            $lastInsertPerentId = Account::create(['category_supplier' => $fileValue->supplier_id, 'customer_number' => $row[$keyParent], 'alies' => $row[$keyParentName], 'parent_id' => $gdPerent->id, 'created_by' => $fileValue->created_by]);

                                            Account::create(['category_supplier' => $fileValue->supplier_id, 'customer_number' => $row[$keyCustomer], 'alies' => $row[$keyCustomerName], 'parent_id' => $lastInsertPerentId->id, 'created_by' => $fileValue->created_by]);

                                        } elseif (!empty($gdPerent) && !empty($perent) && empty($customer)) {
                                            Account::create(['category_supplier' => $fileValue->supplier_id, 'customer_number' => $row[$keyCustomer], 'alies' => $row[$keyCustomerName], 'parent_id' => $perent->id, 'created_by' => $fileValue->created_by]);

                                        } else {
                                            // echo "hello";
                                        }
                                    }

                                    if (in_array($fileValue->supplier_id, [1, 4, 5, 6])) {
                                        $customer = Account::where('customer_number', $row[$keyCustomer])->first();
                                        if (empty($customer)) {
                                            Account::create(['category_supplier' => $fileValue->supplier_id, 'customer_number' => $row[$keyCustomer], 'alies' => $row[$keyCustomerName], 'parent_id' => null, 'created_by' => $fileValue->created_by]);
                                        }
                                    }
                                }
                            }

                            if (isset($workSheetArray1) && !empty($workSheetArray1)) {
                                /** For insert data into the database */
                                foreach ($workSheetArray1 as $key => $row) {
                                    if (count(array_intersect($skipRowArray, $row)) <= 0) {
                                        if (!empty($columnArray[$fileValue->supplier_id]['customer_number'])) {
                                            $keyCustomerNumber = array_search($columnArray[$fileValue->supplier_id]['customer_number'], $maxNonEmptyValue);
                                        }
    
                                        if (!empty($columnArray[$fileValue->supplier_id]['amount'])) {
                                            if ($fileValue->supplier_id == 1) {
                                                $keyOffCoreAmount = array_search($offCoreSpend, $maxNonEmptyValue);
                                            }
    
                                            $keyAmount = array_search($columnArray[$fileValue->supplier_id]['amount'], $maxNonEmptyValue);
                                        }
    
                                        if (!empty($columnArray[$fileValue->supplier_id]['invoice_no'])) {
                                            $keyInvoiceNumber = array_search($columnArray[$fileValue->supplier_id]['invoice_no'], $maxNonEmptyValue);
                                        }
    
                                        if (!empty($columnArray[$fileValue->supplier_id]['invoice_date'])) {
                                            $keyInvoiceDate = array_search($columnArray[$fileValue->supplier_id]['invoice_date'], $maxNonEmptyValue);
                                        }
     
                                        if (isset($keyCustomerNumber) && !empty($row[$keyCustomerNumber])) {
                                            foreach ($row as $key1 => $value) {
                                                if(!empty($maxNonEmptyValue[$key1])) {
                                                    $finalInsertArray[] = [
                                                        'data_id' => $fileValue->id,
                                                        'value' => $value,
                                                        'key' => $maxNonEmptyValue[$key1],
                                                        'file_name' => $fileValue->file_name,
                                                        'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                        'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                    ];  
                                                }
                                            }
    
                                            if ($fileValue->supplier_id == 7) {
                                                foreach ($weeklyPriceColumnArray as $key => $value) {
                                                    if (!empty($row[$key])) {                                                    
                                                        $date = explode("-", $workSheetArray[7][$key]);
    
                                                        $orderLastInsertId = Order::create([
                                                            'data_id' => $fileValue->id,
                                                            'created_by' => $fileValue->created_by,
                                                            'supplier_id' => $fileValue->supplier_id,
                                                            'amount' => str_replace(",", "", number_format($row[$key], 2, '.')),
                                                            'date' =>  (!empty($keyInvoiceDate) && !empty($row[$keyInvoiceDate])) ? (($row[$keyInvoiceDate] && $fileValue->supplier_id == 4) ? (Carbon::createFromFormat('Y-m-d H:i:s', $row[$keyInvoiceDate])->format('Y-m-d H:i:s')) : (Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[$keyInvoiceDate]))->format('Y-m-d H:i:s'))) : ($fileValue->start_date),
                                                            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                            'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                            'customer_number' => (!empty($keyCustomerNumber) && !empty($row[$keyCustomerNumber])) ? ($row[$keyCustomerNumber]) : (''),
                                                        ]);
    
                                                        if ($weeklyCheck) {
                                                            OrderDetails::create([
                                                                'data_id' => $fileValue->id,
                                                                'order_id' => $orderLastInsertId->id,
                                                                'created_by' => $fileValue->created_by,
                                                                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                                'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                                'invoice_date' => (!empty($keyInvoiceDate) && !empty($row[$keyInvoiceDate])) ? (($row[$keyInvoiceDate] && $fileValue->supplier_id == 4) ? (Carbon::createFromFormat('Y-m-d H:i:s', $row[$keyInvoiceDate])->format('Y-m-d H:i:s')) : (Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[$keyInvoiceDate]))->format('Y-m-d H:i:s'))) : ($fileValue->start_date),
                                                                'invoice_number' => (!empty($keyInvoiceNumber) && !empty($row[$keyInvoiceNumber])) ? ($row[$keyInvoiceNumber]) : (OrderDetails::randomInvoiceNum()),
                                                                'order_file_name' => $fileValue->supplier_id."_weekly_".date_format(date_create($fileValue->start_date),"Y/m"),
                                                            ]);
                                                        } else {
                                                            OrderDetails::create([
                                                                'data_id' => $fileValue->id,
                                                                'order_id' => $orderLastInsertId->id,
                                                                'created_by' => $fileValue->created_by,
                                                                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                                'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                                'invoice_number' => (!empty($keyInvoiceNumber) && !empty($row[$keyInvoiceNumber])) ? ($row[$keyInvoiceNumber]) : (OrderDetails::randomInvoiceNum()),
                                                                'invoice_date' => (!empty($keyInvoiceDate) && !empty($row[$keyInvoiceDate])) ? (($row[$keyInvoiceDate] && $fileValue->supplier_id == 4) ? (Carbon::createFromFormat('Y-m-d H:i:s', $row[$keyInvoiceDate])->format('Y-m-d H:i:s')) : (Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[$keyInvoiceDate]))->format('Y-m-d H:i:s'))) : ($fileValue->start_date),
                                                                'order_file_name' => $fileValue->supplier_id."_monthly_".date_format(date_create($fileValue->start_date),"Y/m"),
                                                            ]);
                                                        }
                                                    }
                                                }
                                            } else {
                                                if ($fileValue->supplier_id == 6) {
                                                    $customerNumber = explode(" ", $row[$keyCustomerNumber]);
                                                    $orderLastInsertId = Order::create([
                                                        'data_id' => $fileValue->id,
                                                        'created_by' => $fileValue->created_by,
                                                        'supplier_id' => $fileValue->supplier_id,
                                                        'amount' => (isset($keyAmount) && !empty($row[$keyAmount])) ? ($row[$keyAmount]) : ((!empty($keyOffCoreAmount) && !empty($row[$keyOffCoreAmount]) && $fileValue->supplier_id) ? ($row[$keyOffCoreAmount]) : ('0.0')),
                                                        'date' =>  (isset($keyInvoiceDate) && !empty($row[$keyInvoiceDate])) ? (($row[$keyInvoiceDate] && $fileValue->supplier_id == 4) ? (Carbon::createFromFormat('Y-m-d H:i:s', $row[$keyInvoiceDate])->format('Y-m-d H:i:s')) : (Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[$keyInvoiceDate]))->format('Y-m-d H:i:s'))) : ($fileValue->start_date),
                                                        'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                        'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                        'customer_number' => $customerNumber[0],
                                                    ]);
                                                } else {
                                                    $orderLastInsertId = Order::create([
                                                        'data_id' => $fileValue->id,
                                                        'created_by' => $fileValue->created_by,
                                                        'supplier_id' => $fileValue->supplier_id,
                                                        'amount' => (isset($keyAmount) && !empty($row[$keyAmount])) ? ($row[$keyAmount]) : ((!empty($keyOffCoreAmount) && !empty($row[$keyOffCoreAmount]) && $fileValue->supplier_id) ? ($row[$keyOffCoreAmount]) : ('0.0')),
                                                        'date' =>  (isset($keyInvoiceDate) && !empty($row[$keyInvoiceDate])) ? (($row[$keyInvoiceDate] && $fileValue->supplier_id == 4) ? (Carbon::createFromFormat('Y-m-d H:i:s', $row[$keyInvoiceDate])->format('Y-m-d H:i:s')) : (Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[$keyInvoiceDate]))->format('Y-m-d H:i:s'))) : ($fileValue->start_date),
                                                        'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                        'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                        'customer_number' => $row[$keyCustomerNumber],
                                                    ]);
                                                }
    
                                                if ($weeklyCheck) {
                                                    $orderDetailsArray[] = [
                                                        'data_id' => $fileValue->id,
                                                        'order_id' => $orderLastInsertId->id,
                                                        'created_by' => $fileValue->created_by,
                                                        'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                        'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                        'invoice_date' => (isset($keyInvoiceDate) && !empty($row[$keyInvoiceDate])) ? (($row[$keyInvoiceDate] && $fileValue->supplier_id == 4) ? (Carbon::createFromFormat('Y-m-d H:i:s', $row[$keyInvoiceDate])->format('Y-m-d H:i:s')) : (Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[$keyInvoiceDate]))->format('Y-m-d H:i:s'))) : ($fileValue->start_date),
                                                        'invoice_number' => (isset($keyInvoiceNumber) && !empty($row[$keyInvoiceNumber])) ? ($row[$keyInvoiceNumber]) : (OrderDetails::randomInvoiceNum()),
                                                        'order_file_name' => $fileValue->supplier_id."_weekly_".date_format(date_create($fileValue->start_date),"Y/m"),
                                                    ];
                                                } else {
                                                    $orderDetailsArray[] = [
                                                        'data_id' => $fileValue->id,
                                                        'order_id' => $orderLastInsertId->id,
                                                        'created_by' => $fileValue->created_by,
                                                        'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                        'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                        'invoice_number' => (isset($keyInvoiceNumber) && !empty($row[$keyInvoiceNumber])) ? ($row[$keyInvoiceNumber]) : (OrderDetails::randomInvoiceNum()),
                                                        'invoice_date' => (isset($keyInvoiceDate) && !empty($row[$keyInvoiceDate])) ? (($row[$keyInvoiceDate] && $fileValue->supplier_id == 4) ? (Carbon::createFromFormat('Y-m-d H:i:s', $row[$keyInvoiceDate])->format('Y-m-d H:i:s')) : (Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[$keyInvoiceDate]))->format('Y-m-d H:i:s'))) : ($fileValue->start_date),
                                                        'order_file_name' => $fileValue->supplier_id."_monthly_".date_format(date_create($fileValue->start_date),"Y/m"),
                                                    ];
                                                }
                                            }
    
                                            foreach ($finalInsertArray as &$item) {
                                                if (!isset($item['order_id']) && empty($item['order_id'])) {
                                                    $item['order_id'] = $orderLastInsertId->id;
                                                }
                                            }
                                            if ($count == 70) {
                                                $count = 0;
                                                try {
                                                    if ($fileValue->supplier_id != 7) {
                                                        DB::table('order_details')->insert($orderDetailsArray);
                                                    }
                                                    DB::table('order_product_details')->insert($finalInsertArray);
                                                } catch (QueryException $e) {   
                                                    Log::error('Error in YourScheduledTask: ' . $e->getMessage());
                                                    echo "Database insertion failed: " . $e->getMessage();
                                                    echo $e->getTraceAsString();
                                                    die;
                                                }
                                                
                                                unset($finalInsertArray, $orderDetailsArray);
                                            }
        
                                            $count++; 
                                        }
                                    } else {
                                        continue;
                                    }
                                }
                            }

                            unset($workSheetArray1, $count, $maxNonEmptyValue);
                            if (isset($finalInsertArray) && !empty($finalInsertArray)) {
                                try {
                                    if ($fileValue->supplier_id != 7) {
                                        if (isset($orderDetailsArray) && !empty($orderDetailsArray)) {
                                            DB::table('order_details')->insert($orderDetailsArray);
                                        }
                                    }
                                    DB::table('order_product_details')->insert($finalInsertArray);
                                } catch (QueryException $e) {   
                                    Log::error('Error in YourScheduledTask: ' . $e->getMessage());
                                    echo "Database insertion failed: " . $e->getMessage();
                                }
                            }

                            unset($finalInsertArray, $finalOrderInsertArray);
                        }
                } catch (\Exception $e) {
                    echo "Error loading spreadsheet: " . $e->getMessage();
                }

                try {
                    /** Update the 'cron' field three after processing done */
                    DB::table('uploaded_files')->where('id', $fileValue->id)->update(['cron' => UploadedFiles::PROCESSED]);

                    $this->info('Uploaded files processed successfully.');
                } catch (QueryException $e) {   
                    echo "Database updation failed: " . $e->getMessage();
                    die;
                }
            } else {
                echo "No file left to process.";
            }
        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
            echo "Error loading spreadsheet: " . $e->getMessage();
            die;
        } catch (QueryException $e) {   
            echo "Database table uploaded_files select query failed: " . $e->getMessage();
            die;
        }  
    }
}
