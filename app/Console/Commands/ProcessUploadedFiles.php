<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\QueryException;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use App\Models\{
    Order,
    Account,
    OrderDetails,
    UploadedFiles
};

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
            $fileValue = DB::table('uploaded_files')->select('id', 'supplier_id', 'file_name', 'start_date', 'end_date', 'created_by')->where('cron', '=', 11)->whereNull('deleted_by')->first();
            
            if ($fileValue !== null) {
                /** Update cron two means start processing data into excel */
                DB::table('uploaded_files')->where('id', $fileValue->id)
                ->update([
                    'cron' => UploadedFiles::CRON
                ]);

                /** Add column name here those row you want to skip */
                $skipRowArray = ["Shipto Location Total", "Shipto & Location Total", "TOTAL FOR ALL LOCATIONS", "Total"];
                 
                $columnValues = DB::table('manage_columns')
                ->select(
                    'manage_columns.id as id',
                    'manage_columns.field_name as field_name',
                    'manage_columns.supplier_id as supplier_id',
                    'requird_fields.id as required_field_id',
                    'requird_fields.field_name as required_field_column',
                )
                ->leftJoin('requird_fields', 'manage_columns.required_field_id', '=', 'requird_fields.id')
                ->where('supplier_id', $fileValue->supplier_id)
                ->get();

                $date = '';
                foreach ($columnValues as $key => $value) {
                    if (!empty($value->required_field_column)) {
                        $columnArray[$value->supplier_id][$value->required_field_column] = $value->field_name;
                    }

                    if ($value->supplier_id == 7) {
                        $columnArray[$value->supplier_id]['amount'] = '';
                        $columnArray[$value->supplier_id]['invoice_date'] = '';
                    }

                    if ($value->required_field_id == 9) {
                        $date = preg_replace('/^_+|_+$/', '', strtolower(preg_replace('/[^A-Za-z0-9_]/', '', str_replace(' ', '_', $value->field_name))));
                    }

                    if (in_array($value->supplier_id, [1, 7])) {
                        $columnArray[$value->supplier_id]['invoice_no'] = '';
                    }

                    $columnArray1[$value->id] = $value->field_name;
                }

                $columnArray2 = [];
                foreach ($columnArray1 as $key => $value) {
                    $columnArray2[$fileValue->supplier_id][$value] = preg_replace('/^_+|_+$/', '', strtolower(preg_replace('/[^A-Za-z0-9_]/', '', str_replace(' ', '_', $value)))); 
                }                

                try {
                    /** Increasing the memory limit becouse memory limit issue */
                    ini_set('memory_limit', '1024M');

                    /** Inserting files data into the database after doing excel import */
                        $weeklyCheck = true;
                       
                        unset($spreadSheet, $reader);

                        $inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($destinationPath . '/' . $fileValue->file_name);

                        if ($inputFileType === 'Xlsx') {
                            $reader = new Xlsx();
                        } elseif ($inputFileType === 'Xls') {
                            $reader = new Xls();
                        } else {
                            // throw new Exception('Unsupported file type: ' . $inputFileType);
                        }

                        /** Loading excel file using path and name of file from table "uploaded_file" */
                        $spreadSheet = $reader->load($destinationPath . '/' . $fileValue->file_name, 2);
                        $sheetCount = $spreadSheet->getSheetCount(); /** Getting sheet count for run loop on index */
                        
                        if ($fileValue->supplier_id == 4 || $fileValue->supplier_id == 3) {
                            $sheetCount = ($sheetCount > 1) ? $sheetCount - 2 : $sheetCount; /** Handle case if sheet count is one */
                        } else {
                            $sheetCount = ($sheetCount > 1) ? $sheetCount - 1 : $sheetCount;
                        }

                        $supplierFilesNamesArray = [
                            1 => 'Usage By Location and Item',
                            2 => 'Invoice Detail Report',
                            // 3 => '',
                            4 => 'All Shipped Order Detail',
                            5 => 'Centerpoint_Summary_Report',
                            6 => 'Blad1',
                            7 => 'Weekly Sales Account Summary', 
                        ];

                        DB::table('uploaded_files')
                        ->where('id', $fileValue->id)
                        ->update([
                            'cron' => 4
                        ]);

                        for ($i = 0; $i <= $sheetCount; $i++) {
                            $count = $maxNonEmptyCount = 0;
                            
                            // if ($fileValue->supplier_id == 5 && $i == 1) {
                            //     continue;
                            // }
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

                            if ($fileValue->supplier_id == 4) {
                                $columnArray2[$fileValue->supplier_id]["Group ID1"] = 'group_id';
                                $columnArray2[$fileValue->supplier_id]["Payment Method Code1"] = 'payment_method_code';
                                $columnArray2[$fileValue->supplier_id]["Transaction Source System1"] = 'transaction_source_system';
                                $maxNonEmptyValue[36] = "Payment Method Code1";
                                $maxNonEmptyValue[37] = "Payment Method Code";
                                $maxNonEmptyValue[42] = "Transaction Source System1";
                                $maxNonEmptyValue[43] = "Transaction Source System";
                                $maxNonEmptyValue[44] = "Group ID1";
                                $maxNonEmptyValue[45] = "Group ID";
                            }

                            // dd($maxNonEmptyValue, $columnArray2);
                            /** Clean up the values */
                            $maxNonEmptyValue = array_map(function ($value) {
                                /** Remove line breaks and trim whitespace */
                                return str_replace(["\r", "\n"], '', $value);
                            }, $maxNonEmptyValue);

                            if ($fileValue->supplier_id == 7) {
                                $weeklyPriceColumnArray = [];
                                foreach ($maxNonEmptyValue as $key => $value) {
                                    if ($key >= 6) {
                                        $weeklyPriceColumnArray[$key] = $value;
                                        // $weeklyArrayKey++;
                                    }
                                }
                            }

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

                                    if ((($fileValue->supplier_id == 2 && $key > $graingerCount) || $fileValue->supplier_id == 3 || $fileValue->supplier_id == 7) || count($columnArray[$fileValue->supplier_id]) > 5) {
                                        if (isset($row[$keyCustomer]) && !empty($row[$keyCustomer])) {
                                            $customers = Account::where('account_number', 'LIKE', '%' . ltrim($row[$keyCustomer], '0') . '%')->first();
                                            if (empty($customers)) {
                                                if (strpos($row[$keyParentName], "CenterPoint") !== false) {
                                                    Account::create([
                                                        'parent_id' => $row[$keyParent],
                                                        'parent_name' => $row[$keyParentName],
                                                        'account_number' => $row[$keyCustomer],
                                                        'account_name' => $row[$keyCustomerName],
                                                        'customer_name' => $row[$keyCustomerName],
                                                        'grandparent_id' => $row[$keyGrandParent],
                                                        'category_supplier' => (($fileValue->supplier_id == 7) ? (3) : ($fileValue->supplier_id)) ,
                                                        'grandparent_name' => $row[$keyGrandParentName],
                                                    ]);
                                                } else {
                                                    Account::create([
                                                        'parent_id' => $row[$keyParent],
                                                        'parent_name' => $row[$keyParentName],
                                                        'account_number' => $row[$keyCustomer],
                                                        'customer_name' => $row[$keyCustomerName],
                                                        'grandparent_id' => $row[$keyGrandParent],
                                                        'category_supplier' => (($fileValue->supplier_id == 7) ? (3) : ($fileValue->supplier_id)) ,
                                                        'grandparent_name' => $row[$keyGrandParentName],
                                                    ]);
                                                }
                                            } else {
                                                Account::where('account_number', 'LIKE', '%' . ltrim($row[$keyCustomer], '0') . '%')->update([
                                                    'parent_id' => $row[$keyParent],
                                                    'parent_name' => $row[$keyParentName],
                                                    'account_number' => ltrim($row[$keyCustomer], '0'),
                                                    'customer_name' => $row[$keyCustomerName],
                                                    'grandparent_id' => $row[$keyGrandParent],
                                                    'category_supplier' => (($fileValue->supplier_id == 7) ? (3) : ($fileValue->supplier_id)),
                                                    'grandparent_name' => $row[$keyGrandParentName],
                                                ]);
                                            }
                                        }
                                    }

                                    if (in_array($fileValue->supplier_id, [1, 4, 5, 6]) || count($columnArray[$fileValue->supplier_id]) <= 5) {
                                        if (isset($row[$keyCustomer]) && !empty($row[$keyCustomer])) {
                                            $customers = Account::where('account_number', 'LIKE', '%' . ltrim($row[$keyCustomer], '0') . '%')->first();
                                            if (empty($customers)) {
                                                Account::create([
                                                    'account_number' => ltrim($row[$keyCustomer], '0'),
                                                    'customer_name' => $row[$keyCustomerName],
                                                    'category_supplier' => $fileValue->supplier_id,
                                                ]);
                                            } else {
                                                Account::where('account_number', 'LIKE', '%' . ltrim($row[$keyCustomer], '0') . '%')->update([
                                                    'account_number' => ltrim($row[$keyCustomer], '0'),
                                                    'customer_name' => $row[$keyCustomerName],
                                                    'category_supplier' => $fileValue->supplier_id,
                                                ]);
                                            }
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
                                                    if ($fileValue->supplier_id != 7) {
                                                        if ($columnArray2[$fileValue->supplier_id][trim($maxNonEmptyValue[$key1])] == $date) {
                                                            $excelInsertArray[$key][$columnArray2[$fileValue->supplier_id][trim($maxNonEmptyValue[$key1])]] =  (!empty($value)) ? Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value))->format('Y-m-d H:i:s') : ('');
                                                        } else {
                                                            $excelInsertArray[$key][$columnArray2[$fileValue->supplier_id][trim($maxNonEmptyValue[$key1])]] = $value;
                                                        }
                                                    } else {
                                                        if ($key1 < 6) {
                                                            $excelInsertArray[$key][$columnArray2[$fileValue->supplier_id][trim($maxNonEmptyValue[$key1])]] = $value;
                                                        } else {
                                                            $excelInsertArray[$key][$columnArray2[$fileValue->supplier_id][trim("Year_" . substr($maxNonEmptyValue[$key1], - 2))]] = $value;
                                                        }
                                                    }
                                                    
                                                    $excelInsertArray[$key]['data_id'] = $fileValue->id;
                                                    $excelInsertArray[$key]['created_at'] = Carbon::now()->format('Y-m-d H:i:s');
                                                    $excelInsertArray[$key]['updated_at'] = Carbon::now()->format('Y-m-d H:i:s');

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
                                                            'supplier_id' => (($fileValue->supplier_id == 7) ? (3) : ($fileValue->supplier_id)),
                                                            'amount' => str_replace(",", "", number_format($row[$key], 2, '.')),
                                                            'date' =>  (!empty($keyInvoiceDate) && !empty($row[$keyInvoiceDate])) ? (($row[$keyInvoiceDate] && $fileValue->supplier_id == 4) ? (Carbon::createFromFormat('Y-m-d H:i:s', $row[$keyInvoiceDate])->format('Y-m-d H:i:s')) : (Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[$keyInvoiceDate]))->format('Y-m-d H:i:s'))) : ($fileValue->start_date),
                                                            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                            'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                            'customer_number' => (!empty($keyCustomerNumber) && !empty($row[$keyCustomerNumber])) ? (ltrim($row[$keyCustomerNumber], "0")) : (''),
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
                                                        'amount' => $row[$keyAmount],
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
                                                        'amount' => $row[$keyAmount],
                                                        'date' => (isset($keyInvoiceDate) && !empty($row[$keyInvoiceDate])) ? (Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[$keyInvoiceDate]))->format('Y-m-d H:i:s')) : ($fileValue->start_date),
                                                        'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                        'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                        'customer_number' => ltrim($row[$keyCustomerNumber], '0'),
                                                    ]);
                                                }
    
                                                if ($weeklyCheck) {
                                                    $orderDetailsArray[] = [
                                                        'data_id' => $fileValue->id,
                                                        'order_id' => $orderLastInsertId->id,
                                                        'created_by' => $fileValue->created_by,
                                                        'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                        'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                        'invoice_date' => (isset($keyInvoiceDate) && !empty($row[$keyInvoiceDate])) ? (Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[$keyInvoiceDate]))->format('Y-m-d H:i:s')) : ($fileValue->start_date),
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
                                                        'invoice_date' => (isset($keyInvoiceDate) && !empty($row[$keyInvoiceDate])) ? (Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[$keyInvoiceDate]))->format('Y-m-d H:i:s')) : ($fileValue->start_date),
                                                        'order_file_name' => $fileValue->supplier_id."_monthly_".date_format(date_create($fileValue->start_date),"Y/m"),
                                                    ];
                                                }
                                            }
                                            // dd($excelInsertArray);

                                            foreach ($finalInsertArray as &$item) {
                                                if (!isset($item['order_id']) && empty($item['order_id'])) {
                                                    $item['order_id'] = $orderLastInsertId->id;
                                                }
                                            }
                                            if ($count == 70) {
                                                $count = 0;
                                                try {
                                                    DB::table(DB::table('supplier_tables')->select('table_name')->where('supplier_id', $fileValue->supplier_id)->first()->table_name)->insert($excelInsertArray);

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
                                                
                                                unset($finalInsertArray, $orderDetailsArray, $excelInsertArray);
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
                                    DB::table('uploaded_files')
                                    ->where('id', $fileValue->id)
                                    ->update([
                                        'cron' => 5
                                    ]);

                                    DB::table(DB::table('supplier_tables')->select('table_name')->where('supplier_id', $fileValue->supplier_id)->first()->table_name)->insert($excelInsertArray);

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

                            unset($finalInsertArray, $finalOrderInsertArray, $excelInsertArray);
                        }
                    try {
                        /** Update the 'cron' field three after processing done */
                        DB::table('uploaded_files')->where('id', $fileValue->id)->update(['cron' => 6]);
    
                        $this->info('Uploaded files processed successfully.');
                    } catch (QueryException $e) {   
                        echo "Database updation failed: " . $e->getMessage();
                        die;
                    }
                } catch (\Exception $e) {
                    /** Update the 'cron' field three after processing done */
                    // DB::table('uploaded_files')->where('id', $fileValue->id)->update(['cron' => 1]);
                    echo "Error loading spreadsheet: " . $e->getMessage();
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
