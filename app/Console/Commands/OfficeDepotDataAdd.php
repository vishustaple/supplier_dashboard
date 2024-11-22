<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\{DB, File, Log};
use Illuminate\Database\QueryException;
use PhpOffice\PhpSpreadsheet\Reader\{Xls, Xlsx};
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use App\Models\{
    Account,
    ManageColumns,
    Order,
    UploadedFiles,
};

class OfficeDepotDataAdd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:office-depot-data-add';

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
        // $userFileExist = DB::table('consolidated_file')
        // ->select('id', 'file_name')
        // ->where('delete_check', 1)
        // ->get();

        // if ($userFileExist->isNotEmpty()) {
        //     foreach ($userFileExist as $key => $value) {
        //         if (File::exists(storage_path('app/' . $value->file_name))) {
        //             try {
        //                 File::delete(storage_path('app/' . $value->file_name));
                        
        //                 /** Delete the database record after the file deletion */
        //                 DB::table('consolidated_file')
        //                     ->where('id', $value->id)
        //                     ->delete();
        //             } catch (\Exception $e) {
        //                 Log::error('Error deleting file: ' . $e->getMessage());
        //             }
        //         } else {
        //             session()->flash('error', 'File not found.');
        //         }
        //     }
        // }

        // /** This is the folder path where we save the file */
        // $destinationPath = public_path('/excel_sheets');

        // // try {
        //     /** Select those file name where cron is one */
        //     $fileValue = DB::table('attachments')
        //     ->select('id', 'supplier_id', 'file_name', 'created_by')
        //     ->where('cron', '=', 11)
        //     ->whereNull('deleted_by')
        //     ->first();
            
        //     $suppliers = ManageColumns::getRequiredColumns();

        //     if ($fileValue !== null) {
        //         DB::table('operational_anomaly_report')->delete();

        //         /** Update cron two means start processing data into excel */
        //         DB::table('attachments')
        //         ->where('id', $fileValue->id)
        //         ->update([
        //             'cron' => UploadedFiles::CRON
        //         ]);

        //         /** Add column name here those row you want to skip */
        //         $skipRowArray = ["Shipto Location Total", "Shipto & Location Total", "TOTAL FOR ALL LOCATIONS", "Total"];
                 
        //         $columnValues = DB::table('supplier_fields')
        //         ->select(
        //             'supplier_fields.id as id',
        //             'supplier_fields.label as label',
        //             'supplier_fields.raw_label as raw_label',
        //             'supplier_fields.supplier_id as supplier_id',
        //             'required_fields.id as required_field_id',
        //             'required_fields.field_name as required_field_column',
        //         )
        //         ->leftJoin('required_fields', 'supplier_fields.required_field_id', '=', 'required_fields.id')
        //         ->where([
        //             'supplier_id' => $fileValue->supplier_id,
        //             'deleted' => 0,
        //         ])
        //         ->get();

        //         $date = '';
        //         $columnArray2 = [];
        //         foreach ($columnValues as $key => $value) {
        //             if (!empty($value->required_field_column)) {
        //                 $columnArray[$value->supplier_id][$value->required_field_column] = $value->label;
        //             }

        //             if ($value->supplier_id == 7) {
        //                 $columnArray[$value->supplier_id]['cost'] = '';
        //                 $columnArray[$value->supplier_id]['invoice_date'] = '';
        //             }

        //             if ($value->required_field_id == 9) {
        //                 $date = preg_replace('/^_+|_+$/', '', strtolower(preg_replace('/[^A-Za-z0-9_]/', '', str_replace(' ', '_', $value->label))));
        //             }

        //             if (in_array($value->supplier_id, [1, 7])) {
        //                 $columnArray[$value->supplier_id]['invoice_no'] = '';
        //             }

        //             $columnArray2[$fileValue->supplier_id][$value->label] = $value->raw_label;
        //             $columnArray1[$value->id] = $value->label;
        //         }

        //         // try {
        //             /** Increasing the memory limit becouse memory limit issue */
        //             ini_set('memory_limit', '1024M');

        //             /** For memory optimization unset the spreadSheet and reader */                       
        //             unset($spreadSheet, $reader);

        //             $inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($destinationPath . '/' . $fileValue->file_name);

        //             if ($inputFileType === 'Xlsx') {
        //                 $reader = new Xlsx();
        //             } elseif ($inputFileType === 'Xls') {
        //                 $reader = new Xls();
        //             } else {
        //                 // throw new Exception('Unsupported file type: ' . $inputFileType);
        //             }

        //             /** Loading excel file using path and name of file from table "uploaded_file" */
        //             $spreadSheet = $reader->load($destinationPath . '/' . $fileValue->file_name, 2);
        //             $sheetCount = $spreadSheet->getSheetCount(); /** Getting sheet count for run loop on index */
                    
        //             if ($fileValue->supplier_id == 4 || $fileValue->supplier_id == 3) {
        //                 $sheetCount = ($sheetCount > 1) ? $sheetCount - 2 : $sheetCount; /** Handle case if sheet count is one */
        //             } else {
        //                 $sheetCount = ($sheetCount > 1) ? $sheetCount - 1 : $sheetCount;
        //             }

        //             // /** Updating the file upload status */
        //             // DB::table('attachments')
        //             // ->where('id', $fileValue->id)
        //             // ->update([
        //             //     'cron' => 4
        //             // ]);

        //             /** Run the for loop for excel sheets */
        //             for ($i = 0; $i <= $sheetCount; $i++) {
        //                 $count = $maxNonEmptyCount = 0;

        //                 if (($sheetCount == 1 && $i == 1 && $fileValue->supplier_id != 5) || ($fileValue->supplier_id == 5 && $i == 0) || ($fileValue->supplier_id == 7 && $i != 2)) {
        //                     continue;
        //                 }

        //                 DB::table('attachments')
        //                 ->where('id', $fileValue->id)
        //                 ->update([
        //                     'cron' => 4
        //                 ]);

        //                 $workSheetArray = $spreadSheet->getSheet($i)->toArray(); /** Getting worksheet using index */

        //                 foreach ($workSheetArray as $key=>$value) {
        //                     $finalExcelKeyArray1 = array_values(array_filter($value, function ($item) {
        //                                 return !empty($item);
        //                             },
        //                             ARRAY_FILTER_USE_BOTH
        //                         )
        //                     );
                                        
        //                     /** Clean up the values */
        //                     $cleanedArray = array_map(function ($values) {
        //                         /** Remove line breaks and trim whitespace */
        //                         return trim(str_replace(["\r", "\n"], '', $values));
        //                     }, $finalExcelKeyArray1);
    
        //                     if (isset($suppliers[$fileValue->supplier_id])) {
        //                         $supplierValues = $suppliers[$fileValue->supplier_id];
        //                         if ($fileValue->supplier_id == 7) {
        //                             $supplierValues = array_slice($supplierValues, 0, 6, true);                        
        //                             if (isset($cleanedArray[5]) && $cleanedArray[5] == $supplierValues[5]) {
        //                                 foreach ($cleanedArray as $keys => $valuess) {
        //                                     if ($keys > 5) {
        //                                         $cleanedArray[$keys] = trim("year_" . substr($cleanedArray[$keys], - 2));
        //                                     }
        //                                 }
        //                             } else {
        //                                 continue;
        //                             }
        //                         }

        //                         $arrayDiff = array_diff($supplierValues, $cleanedArray);

        //                         if (empty($arrayDiff)) {
        //                             $maxNonEmptyValue = $value;
        //                             $startIndexValueArray = $key;
        //                             break;
        //                         }
        //                     }
        //                 }

        //                 if (!isset($maxNonEmptyValue)) {
        //                     continue;
        //                 }

        //                 if ($fileValue->supplier_id == 7) {
        //                     $supplierYear = substr($maxNonEmptyValue[7], 0, 4);
        //                     if (!empty($supplierYear)) {
        //                         $dataIdForDeleteDuplicateData = DB::table(DB::table('supplier_tables')->select('table_name')->where('supplier_id', $fileValue->supplier_id)->first()->table_name)->where('year', $supplierYear)->select('attachment_id')->first();
        //                         if (isset($dataIdForDeleteDuplicateData->attachment_id) && !empty($dataIdForDeleteDuplicateData->attachment_id)) {
        //                             DB::table(DB::table('supplier_tables')->select('table_name')->where('supplier_id', $fileValue->supplier_id)->first()->table_name)->where('year', $supplierYear)->delete();
        //                             DB::table('order_product_details')->where('attachment_id', $dataIdForDeleteDuplicateData->attachment_id)->delete();
        //                             DB::table('order_details')->where('attachment_id', $dataIdForDeleteDuplicateData->attachment_id)->delete();
        //                             DB::table('orders')->where('attachment_id', $dataIdForDeleteDuplicateData->attachment_id)->delete();
        //                         }
        //                     }
        //                 }

        //                 if ($fileValue->supplier_id == 4) {
        //                     $columnArray2[$fileValue->supplier_id]["Group ID1"] = 'group_id';
        //                     $columnArray2[$fileValue->supplier_id]["Payment Method Code1"] = 'payment_method_code';
        //                     $columnArray2[$fileValue->supplier_id]["Transaction Source System1"] = 'transaction_source_system';
        //                     $maxNonEmptyValue[36] = "Payment Method Code1";
        //                     $maxNonEmptyValue[37] = "Payment Method Code";
        //                     $maxNonEmptyValue[42] = "Transaction Source System1";
        //                     $maxNonEmptyValue[43] = "Transaction Source System";
        //                     $maxNonEmptyValue[44] = "Group ID1";
        //                     $maxNonEmptyValue[45] = "Group ID";
        //                 }

        //                 if ($fileValue->supplier_id == 14) {
        //                     $columnArray2[$fileValue->supplier_id]["Transaction Source System1"] = 'transaction_source_system';
        //                     $columnArray2[$fileValue->supplier_id]["Payment Method Code1"] = 'payment_method_code';
        //                     $maxNonEmptyValue[38] = "Payment Method Code1";
        //                     $maxNonEmptyValue[39] = "Payment Method Code";
        //                     $maxNonEmptyValue[44] = "Transaction Source System1";
        //                     $maxNonEmptyValue[45] = "Transaction Source System";
        //                 }


        //                 foreach ($workSheetArray as $key=>$value) {
        //                     $finalExcelKeyArray1 = array_values(array_filter($value, function ($item) {
        //                         return !empty($item);
        //                     }, ARRAY_FILTER_USE_BOTH));
                                        
        //                     /** Clean up the values */
        //                     $cleanedArray = array_map(function ($values) {
        //                         /** Remove line breaks and trim whitespace */
        //                         return trim(str_replace(["\r", "\n"], '', $values));
        //                     }, $finalExcelKeyArray1);

        //                     if (isset($suppliers[$fileValue->supplier_id])) {
        //                         $supplierValues = $suppliers[$fileValue->supplier_id];

        //                         if ($fileValue->supplier_id == 7) {
        //                             /** Extract first 6 elements with original keys preserved */
        //                             $supplierValues = array_slice($supplierValues, 0, 6, true);
        //                         }

        //                         if ($fileValue->supplier_id == 4) {
        //                             /** Check if 'Group ID', 'Payment Method Code' and 'Transaction Source System' exists in the array */
        //                             $groupIndex = array_search('Group ID', $cleanedArray);
        //                             $paymentMethodCodeIndex = array_search('Payment Method Code', $cleanedArray);
        //                             $transactionSourceSystemIndex = array_search('Transaction Source System', $cleanedArray);
        
        //                             $groupIndex !== false ? array_splice($cleanedArray, $groupIndex + 1, 0, 'Group ID1') : '';
        //                             $paymentMethodCodeIndex !== false ? array_splice($cleanedArray, $paymentMethodCodeIndex + 1, 0, 'Payment Method Code1') : '';
        //                             $transactionSourceSystemIndex !== false ? array_splice($cleanedArray, $transactionSourceSystemIndex + 1, 0, 'Transaction Source System1') : '';                            
        //                         }

        //                         if ($fileValue->supplier_id == 14) {
        //                             /** Check if 'Group ID', 'Payment Method Code' and 'Transaction Source System' exists in the array */
        //                             $paymentMethodCodeIndex = array_search('Payment Method Code', $cleanedArray);
        //                             $transactionSourceSystemIndex = array_search('Transaction Source System', $cleanedArray);
        
        //                             $paymentMethodCodeIndex !== false ? array_splice($cleanedArray, $paymentMethodCodeIndex + 1, 0, 'Payment Method Code1') : '';
        //                             $transactionSourceSystemIndex !== false ? array_splice($cleanedArray, $transactionSourceSystemIndex + 1, 0, 'Transaction Source System1') : '';                            
        //                         }

        //                         /** Calculate the difference between the $supplierValues array and the $cleanedArray */
        //                         /** This returns an array containing all elements from $supplierValues that are not present in $cleanedArray */
        //                         $arrayDiff = array_diff($supplierValues, $cleanedArray);

        //                         if (empty($arrayDiff)) {
        //                             $maxNonEmptyValue = $cleanedArray;
        //                             $startIndexValueArray = $key;
        //                             break;
        //                         }
        //                     }
        //                 }

        //                 if (!isset($maxNonEmptyValue)) {
        //                     continue;
        //                 }

        //                 if ($fileValue->supplier_id == 7) {
        //                     $supplierYear = substr($maxNonEmptyValue[7], 0, 4);
        //                     if (!empty($supplierYear)) {
        //                         /** Getting the duplicate data */
        //                         $dataIdForDeleteDuplicateData = DB::table(DB::table('supplier_tables')->select('table_name')->where('supplier_id', $fileValue->supplier_id)->first()->table_name)->where('year', $supplierYear)->select('attachment_id')->first();
                                
        //                         /** If duplicate data exist then delete */
        //                         if (isset($dataIdForDeleteDuplicateData->attachment_id) && !empty($dataIdForDeleteDuplicateData->attachment_id)) {
        //                             /** Deleting data into supplier table, order detail and orders table */
        //                             DB::table(DB::table('supplier_tables')->select('table_name')->where('supplier_id', $fileValue->supplier_id)->first()->table_name)->where('year', $supplierYear)->delete();
        //                             DB::table('order_details')->where('attachment_id', $dataIdForDeleteDuplicateData->attachment_id)->delete();
        //                             DB::table('orders')->where('attachment_id', $dataIdForDeleteDuplicateData->attachment_id)->delete();
        //                         }
        //                     }
        //                 }

        //                 /** Clean up the values */
        //                 $maxNonEmptyValue = array_map(function ($value) {
        //                     /** Remove line breaks and trim whitespace */
        //                     return str_replace(["\r", "\n"], '', $value);
        //                 }, $maxNonEmptyValue);

        //                 /** In case of od weekly we need to create price array with accounts */
        //                 if ($fileValue->supplier_id == 7) {
        //                     $weeklyPriceColumnArray = [];
        //                     foreach ($maxNonEmptyValue as $key => $value) {
        //                         if ($key >= 6) {
        //                             $weeklyPriceColumnArray[$key] = $value;
        //                         }
        //                     }
        //                 }

        //                 /** Unset the "$maxNonEmptyCount" for memory save */
        //                 unset($maxNonEmptyCount);

        //                 $startIndex = $startIndexValueArray; /** Specify the starting index for get the excel column value */

        //                 /** Unset the "$startIndexValueArray" for memory save */
        //                 unset($startIndexValueArray);

        //                 /** In case of grainer we will skip first row because it contain the total amount */
        //                 if ($fileValue->supplier_id == 2) {
        //                     $graingerCount = $startIndex + 1;
        //                 }
                        
        //                 foreach ($workSheetArray as $key => $row) {
        //                     if ($key > $startIndex) {
        //                         $workSheetArray1[] = $row;
        //                         /** Here we will getting the grand parent customer number key */
        //                         if (!empty($columnArray[$fileValue->supplier_id]['gd_customer_number'])) {
        //                             $keyGrandParent = array_search($columnArray[$fileValue->supplier_id]['gd_customer_number'], $maxNonEmptyValue);
        //                         }

        //                         /** Here we will getting the parent customer number key */
        //                         if (!empty($columnArray[$fileValue->supplier_id]['p_customer_number'])) {
        //                             $keyParent = array_search($columnArray[$fileValue->supplier_id]['p_customer_number'], $maxNonEmptyValue);
        //                         }

        //                         /** Here we will getting the customer number key */
        //                         if (!empty($columnArray[$fileValue->supplier_id]['customer_number'])) {
        //                             $keyCustomer = array_search($columnArray[$fileValue->supplier_id]['customer_number'], $maxNonEmptyValue);
        //                         }

        //                         /** Here we will getting the grand parent customer name key */
        //                         if (!empty($columnArray[$fileValue->supplier_id]['gd_customer_name'])) {
        //                             $keyGrandParentName = array_search($columnArray[$fileValue->supplier_id]['gd_customer_name'], $maxNonEmptyValue);
        //                         }

        //                         /** Here we will getting the grand parent customer name key */
        //                         if (!empty($columnArray[$fileValue->supplier_id]['p_customer_name'])) {
        //                             $keyParentName = array_search($columnArray[$fileValue->supplier_id]['p_customer_name'], $maxNonEmptyValue);
        //                         }

        //                         /** Here we will getting the grand parent customer name key */
        //                         if (!empty($columnArray[$fileValue->supplier_id]['customer_name'])) {
        //                             $keyCustomerName = array_search($columnArray[$fileValue->supplier_id]['customer_name'], $maxNonEmptyValue);
        //                         }

        //                         if ((($fileValue->supplier_id == 2 && $key > $graingerCount) || $fileValue->supplier_id == 3 || $fileValue->supplier_id == 7) || count($columnArray[$fileValue->supplier_id]) > 5) {
        //                             if (isset($row[$keyCustomer]) && !empty($row[$keyCustomer])) {
        //                                 /** If account exist than we update the account otherwish we insert new account into 
        //                                  * account table
        //                                  */
        //                                 $customers = Account::where('account_number', 'LIKE', '%' . ltrim($row[$keyCustomer], '0') . '%')->first();

        //                                 $customerId = DB::table('customers')
        //                                 ->where('customer_name', $row[$keyCustomerName])
        //                                 ->first();

        //                                 if (!isset($customerId) && empty($customerId)) {
        //                                     $insertId = DB::table('customers')
        //                                     ->insertGetId([
        //                                         'customer_name' => $row[$keyCustomerName]
        //                                     ]);

        //                                     DB::table('customer_suppliers')
        //                                     ->insert([
        //                                         'customer_id' => $insertId,
        //                                         'supplier_id' => $fileValue->supplier_id,
        //                                     ]);
        //                                 } else {
        //                                     $insertId = $customerId->id;
        //                                 }

        //                                 if (empty($customers)) {
        //                                     if (strpos($row[$keyParentName], "CenterPoint") !== false || strpos($row[$keyParentName], "centerpoint") !== false) {
        //                                         Account::create([
        //                                             'parent_id' => (!empty($keyParent)) ? ($row[$keyParent]) : (''),
        //                                             'parent_name' => (!empty($keyParentName)) ? ($row[$keyParentName]) : (''),
        //                                             'account_number' => (!empty($keyCustomer)) ? ($row[$keyCustomer]) : (''),
        //                                             'account_name' => (!empty($keyCustomerName)) ? ($row[$keyCustomerName]) : (''),
        //                                             'customer_id' => $insertId,
        //                                             'grandparent_id' => (!empty($keyGrandParent)) ? ($row[$keyGrandParent]) : (''),
        //                                             'supplier_id' => (($fileValue->supplier_id == 7) ? (3) : ($fileValue->supplier_id)) ,
        //                                             'grandparent_name' => (!empty($keyGrandParentName)) ? ($row[$keyGrandParentName]) : (''),
        //                                         ]);
        //                                     } else {
        //                                         Account::create([
        //                                             'customer_id' => $insertId,
        //                                             'parent_id' => (!empty($keyParent)) ? ($row[$keyParent]) : (''),
        //                                             'parent_name' => (!empty($keyParentName)) ? ($row[$keyParentName]) : (''),
        //                                             'account_number' => (!empty($keyCustomer)) ? ($row[$keyCustomer]) : (''),
        //                                             'account_name' => (!empty($keyParentName)) ? ($row[$keyParentName]) : (''),
        //                                             'grandparent_id' => (!empty($keyGrandParent)) ? ($row[$keyGrandParent]) : (''),
        //                                             'grandparent_name' => (!empty($keyGrandParentName)) ? ($row[$keyGrandParentName]) : (''),
        //                                             'supplier_id' => (($fileValue->supplier_id == 7) ? (3) : ($fileValue->supplier_id)) ,
        //                                         ]);
        //                                     }
        //                                 } else {
        //                                     Account::where('account_number', 'LIKE', '%' . ltrim($row[$keyCustomer], '0') . '%')
        //                                     ->update([
        //                                         'customer_id' => $insertId,
        //                                         'parent_id' => (!empty($keyParent)) ? ($row[$keyParent]) : (''),
        //                                         'parent_name' => (!empty($keyParentName)) ? ($row[$keyParentName]) : (''),
        //                                         'grandparent_id' => (!empty($keyGrandParent)) ? ($row[$keyGrandParent]) : (''),
        //                                         'grandparent_name' => (!empty($keyGrandParentName)) ? ($row[$keyGrandParentName]) : (''),
        //                                         'account_number' => ltrim($row[$keyCustomer], '0'),
        //                                         'supplier_id' => (($fileValue->supplier_id == 7) ? (3) : ($fileValue->supplier_id)),
        //                                     ]);
        //                                 }
        //                             }
        //                         }

        //                         if (!in_array($fileValue->supplier_id, [2, 3, 7]) || count($columnArray[$fileValue->supplier_id]) <= 5) {
        //                             $customerId = DB::table('customers')
        //                             ->where('customer_name', $row[$keyCustomerName])
        //                             ->first();

        //                             if (!isset($customerId) && empty($customerId)) {
        //                                 $insertId = DB::table('customers')
        //                                 ->insertGetId([
        //                                     'customer_name' => $row[$keyCustomerName]
        //                                 ]);

        //                                 DB::table('customer_suppliers')
        //                                 ->insert([
        //                                     'customer_id' => $insertId,
        //                                     'supplier_id' => $fileValue->supplier_id,
        //                                 ]);
        //                             } else {
        //                                 $insertId = $customerId->id;
        //                             }

        //                             /** Into case of some supplier which do not have grand parent and parent we will use this 
        //                              * condition for insert and update into account table */
        //                             if (isset($row[$keyCustomer]) && !empty($row[$keyCustomer])) {
        //                                 $customers = Account::where('account_number', 'LIKE', '%' . ltrim($row[$keyCustomer], '0') . '%')->first();
        //                                 if (empty($customers)) {
        //                                     Account::create([
        //                                         'customer_id' => $insertId,
        //                                         'account_number' => ltrim($row[$keyCustomer], '0'),
        //                                         'supplier_id' => $fileValue->supplier_id,
        //                                     ]);
        //                                 } else {
        //                                     Account::where('account_number', 'LIKE', '%' . ltrim($row[$keyCustomer], '0') . '%')
        //                                     ->update([
        //                                         'customer_id' => $insertId,
        //                                         'account_number' => ltrim($row[$keyCustomer], '0'),
        //                                         'supplier_id' => $fileValue->supplier_id,
        //                                     ]);
        //                                 }
        //                             }
        //                         }
        //                     }
        //                 }
        //             }
                // }
            // }
        // }



        $orders = DB::table('orders')
        ->select('id')
        ->where('supplier_id', 2)
        ->whereYear('date', 2023)
        ->get();

        foreach ($orders as $key => $value) {
            DB::table('order_details')
            ->where('order_id', $value->id)
            ->delete();
        }

        DB::table('orders')
        ->where('supplier_id', 2)
        ->whereYear('date', 2023)
        ->delete();











        // // /** This is the folder path where we save the file */
        // // $destinationPath = public_path('/excel_sheets');

        // // $inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($destinationPath . '/test55.xlsx');

        // // if ($inputFileType === 'Xlsx') {
        // //     $reader = new Xlsx();
        // // } elseif ($inputFileType === 'Xls') {
        // //     $reader = new Xls();
        // // } else {
        // //     /** throw new Exception('Unsupported file type: ' . $inputFileType); */
        // // }

        // // /** Loading excel file using path and name of file from table "uploaded_file" */
        // // $spreadSheet = $reader->load($destinationPath . '/test55.xlsx', 2);
        // // $spreadSheet = $spreadSheet->getSheet(0)->toArray();

        // // foreach ($spreadSheet as $key => $value) {
        // //     if ($key == 0) {
        // //         continue;
        // //     }

        // //     DB::table('check_orders')
        // //     ->insert([
        // //         'master_customer_number' => $value[1],
        // //         'master_customer_name' => $value[2],
        // //         'bill_to_number' => $value[3],
        // //         'bill_to_name' => $value[4],
        // //         'ship_to_number' => $value[5],
        // //         'order_number' => $value[6],
        // //         'ordering_platform' => $value[7],
        // //         'fixed_rate_sales_volume' => $value[13],
        // //     ]);
        // // }

        // /** Increasing the memory limit becouse memory limit issue */
        // ini_set('memory_limit', '1024M');

        // $data = DB::table('staples_orders_data_old_1719496975')->select('id', 'order_date', 'invoice_date')->get();

        // foreach ($data as $key => $value) {
        //     print($value->id." ");

        //     if (!empty($value->order_date)) {
        //         DB::table('staples_orders_data_old_1719496975')
        //         ->where(['id' => $value->id])
        //         ->update(['order_date' => Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->order_date))->format('Y-m-d H:i:s')]);
        //     }

        //     if (!empty($value->invoice_date)) {
        //         DB::table('staples_orders_data_old_1719496975')
        //         ->where(['id' => $value->id])
        //         ->update(['invoice_date' => Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->invoice_date))->format('Y-m-d H:i:s')]); 
        //     }
        //     // DB::table('staples_orders_data')
        //     // ->where(['id' => $value->id])
        //     // ->update(['order_date' => Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->order_date))->format('Y-m-d H:i:s'), 'invoice_date' => Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->invoice_date))->format('Y-m-d H:i:s')]);    
        // }
    }
}