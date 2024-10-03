<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use App\Models\{Order, Account, UploadedFiles, ManageColumns};

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

        try {
            /** Select those file name where cron is one */
            $fileValue = DB::table('attachments')
            ->select('id', 'supplier_id', 'file_name', 'created_by')
            ->where('cron', '=', 11)
            ->whereNull('deleted_by')
            ->first();
            
            $suppliers = ManageColumns::getRequiredColumns();

            if ($fileValue !== null) {
                DB::table('operational_anomaly_report')->delete();

                /** Update cron two means start processing data into excel */
                DB::table('attachments')
                ->where('id', $fileValue->id)
                ->update([
                    'cron' => UploadedFiles::CRON
                ]);

                /** Add column name here those row you want to skip */
                $skipRowArray = ["Shipto Location Total", "Shipto & Location Total", "TOTAL FOR ALL LOCATIONS", "Total"];
                 
                $columnValues = DB::table('supplier_fields')
                ->select(
                    'supplier_fields.id as id',
                    'supplier_fields.label as label',
                    'supplier_fields.raw_label as raw_label',
                    'supplier_fields.supplier_id as supplier_id',
                    'required_fields.id as required_field_id',
                    'required_fields.field_name as required_field_column',
                )
                ->leftJoin('required_fields', 'supplier_fields.required_field_id', '=', 'required_fields.id')
                ->where([
                    'supplier_id' => $fileValue->supplier_id,
                    'deleted' => 0,
                ])
                ->get();

                $date = '';
                $columnArray2 = [];
                foreach ($columnValues as $key => $value) {
                    if (!empty($value->required_field_column)) {
                        $columnArray[$value->supplier_id][$value->required_field_column] = $value->label;
                    }

                    if ($value->supplier_id == 7) {
                        $columnArray[$value->supplier_id]['cost'] = '';
                        $columnArray[$value->supplier_id]['invoice_date'] = '';
                    }

                    if ($value->required_field_id == 9) {
                        $date = preg_replace('/^_+|_+$/', '', strtolower(preg_replace('/[^A-Za-z0-9_]/', '', str_replace(' ', '_', $value->label))));
                    }

                    if (in_array($value->supplier_id, [1, 7])) {
                        $columnArray[$value->supplier_id]['invoice_no'] = '';
                    }

                    $columnArray2[$fileValue->supplier_id][$value->label] = $value->raw_label;
                    $columnArray1[$value->id] = $value->label;
                }

                try {
                    /** Increasing the memory limit becouse memory limit issue */
                    ini_set('memory_limit', '1024M');

                    /** For memory optimization unset the spreadSheet and reader */                       
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
                        4 => 'All Shipped Order Detail',
                        5 => 'Centerpoint_Summary_Report',
                        6 => 'Blad1',
                        7 => 'Weekly Sales Account Summary', 
                    ];

                    /** Updating the file upload status */
                    DB::table('attachments')
                    ->where('id', $fileValue->id)
                    ->update([
                        'cron' => 4
                    ]);

                    /** Run the for loop for excel sheets */
                    for ($i = 0; $i <= $sheetCount; $i++) {
                        $count = $maxNonEmptyCount = 0;

                        if (($sheetCount == 1 && $i == 1 && $fileValue->supplier_id != 5) || ($fileValue->supplier_id == 5 && $i == 0) || ($fileValue->supplier_id == 7 && $i != 2)) {
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

                        foreach ($workSheetArray as $key=>$value) {
                            $finalExcelKeyArray1 = array_values(array_filter($value, function ($item) {
                                return !empty($item);
                            }, ARRAY_FILTER_USE_BOTH));
                                        
                            /** Clean up the values */
                            $cleanedArray = array_map(function ($values) {
                                /** Remove line breaks and trim whitespace */
                                return trim(str_replace(["\r", "\n"], '', $values));
                            }, $finalExcelKeyArray1);

                            if (isset($suppliers[$fileValue->supplier_id])) {
                                $supplierValues = $suppliers[$fileValue->supplier_id];

                                if ($fileValue->supplier_id == 7) {
                                    /** Extract first 6 elements with original keys preserved */
                                    $supplierValues = array_slice($supplierValues, 0, 6, true);
                                }

                                if ($fileValue->supplier_id == 4) {
                                    /** Check if 'Group ID', 'Payment Method Code' and 'Transaction Source System' exists in the array */
                                    $groupIndex = array_search('Group ID', $cleanedArray);
                                    $paymentMethodCodeIndex = array_search('Payment Method Code', $cleanedArray);
                                    $transactionSourceSystemIndex = array_search('Transaction Source System', $cleanedArray);
        
                                    $groupIndex !== false ? array_splice($cleanedArray, $groupIndex + 1, 0, 'Group ID1') : '';
                                    $paymentMethodCodeIndex !== false ? array_splice($cleanedArray, $paymentMethodCodeIndex + 1, 0, 'Payment Method Code1') : '';
                                    $transactionSourceSystemIndex !== false ? array_splice($cleanedArray, $transactionSourceSystemIndex + 1, 0, 'Transaction Source System1') : '';                            
                                }

                                /** Calculate the difference between the $supplierValues array and the $cleanedArray */
                                /** This returns an array containing all elements from $supplierValues that are not present in $cleanedArray */
                                $arrayDiff = array_diff($supplierValues, $cleanedArray);

                                if (empty($arrayDiff)) {
                                    $maxNonEmptyValue = $cleanedArray;
                                    $startIndexValueArray = $key;
                                    break;
                                }
                            }
                        }

                        if (!isset($maxNonEmptyValue)) {
                            continue;
                        }

                        if ($fileValue->supplier_id == 7) {
                            $supplierYear = substr($maxNonEmptyValue[7], 0, 4);
                            if (!empty($supplierYear)) {
                                /** Getting the duplicate data */
                                $dataIdForDeleteDuplicateData = DB::table(DB::table('supplier_tables')->select('table_name')->where('supplier_id', $fileValue->supplier_id)->first()->table_name)->where('year', $supplierYear)->select('attachment_id')->first();
                                
                                /** If duplicate data exist then delete */
                                if (isset($dataIdForDeleteDuplicateData->attachment_id) && !empty($dataIdForDeleteDuplicateData->attachment_id)) {
                                    /** Deleting data into supplier table, order detail and orders table */
                                    DB::table(DB::table('supplier_tables')->select('table_name')->where('supplier_id', $fileValue->supplier_id)->first()->table_name)->where('year', $supplierYear)->delete();
                                    DB::table('order_details')->where('attachment_id', $dataIdForDeleteDuplicateData->attachment_id)->delete();
                                    DB::table('orders')->where('attachment_id', $dataIdForDeleteDuplicateData->attachment_id)->delete();
                                }
                            }
                        }

                        /** Clean up the values */
                        $maxNonEmptyValue = array_map(function ($value) {
                            /** Remove line breaks and trim whitespace */
                            return str_replace(["\r", "\n"], '', $value);
                        }, $maxNonEmptyValue);

                        /** In case of od weekly we need to create price array with accounts */
                        if ($fileValue->supplier_id == 7) {
                            $weeklyPriceColumnArray = [];
                            foreach ($maxNonEmptyValue as $key => $value) {
                                if ($key >= 6) {
                                    $weeklyPriceColumnArray[$key] = $value;
                                }
                            }
                        }

                        /** Unset the "$maxNonEmptyCount" for memory save */
                        unset($maxNonEmptyCount);

                        $startIndex = $startIndexValueArray; /** Specify the starting index for get the excel column value */

                        /** Unset the "$startIndexValueArray" for memory save */
                        unset($startIndexValueArray);

                        /** In case of grainer we will skip first row because it contain the total amount */
                        if ($fileValue->supplier_id == 2) {
                            $graingerCount = $startIndex + 1;
                        }
                        
                        foreach ($workSheetArray as $key => $row) {
                            if ($key > $startIndex) {
                                $workSheetArray1[] = $row;
                                /** Here we will getting the grand parent customer number key */
                                if (!empty($columnArray[$fileValue->supplier_id]['gd_customer_number'])) {
                                    $keyGrandParent = array_search($columnArray[$fileValue->supplier_id]['gd_customer_number'], $maxNonEmptyValue);
                                }

                                /** Here we will getting the parent customer number key */
                                if (!empty($columnArray[$fileValue->supplier_id]['p_customer_number'])) {
                                    $keyParent = array_search($columnArray[$fileValue->supplier_id]['p_customer_number'], $maxNonEmptyValue);
                                }

                                /** Here we will getting the customer number key */
                                if (!empty($columnArray[$fileValue->supplier_id]['customer_number'])) {
                                    $keyCustomer = array_search($columnArray[$fileValue->supplier_id]['customer_number'], $maxNonEmptyValue);
                                }

                                /** Here we will getting the grand parent customer name key */
                                if (!empty($columnArray[$fileValue->supplier_id]['gd_customer_name'])) {
                                    $keyGrandParentName = array_search($columnArray[$fileValue->supplier_id]['gd_customer_name'], $maxNonEmptyValue);
                                }

                                /** Here we will getting the grand parent customer name key */
                                if (!empty($columnArray[$fileValue->supplier_id]['p_customer_name'])) {
                                    $keyParentName = array_search($columnArray[$fileValue->supplier_id]['p_customer_name'], $maxNonEmptyValue);
                                }

                                /** Here we will getting the grand parent customer name key */
                                if (!empty($columnArray[$fileValue->supplier_id]['customer_name'])) {
                                    $keyCustomerName = array_search($columnArray[$fileValue->supplier_id]['customer_name'], $maxNonEmptyValue);
                                }

                                if ((($fileValue->supplier_id == 2 && $key > $graingerCount) || $fileValue->supplier_id == 3 || $fileValue->supplier_id == 7) || count($columnArray[$fileValue->supplier_id]) > 5) {
                                    if (isset($row[$keyCustomer]) && !empty($row[$keyCustomer])) {
                                        /** If account exist than we update the account otherwish we insert new account into 
                                         * account table
                                         */
                                        $customers = Account::where('account_number', 'LIKE', '%' . ltrim($row[$keyCustomer], '0') . '%')->first();

                                        $customerId = DB::table('customers')
                                        ->where('customer_name', $row[$keyCustomerName])
                                        ->first();

                                        if (!isset($customerId) && empty($customerId)) {
                                            $insertId = DB::table('customers')
                                            ->insert(['customer_name' => $row[$keyCustomerName]]);
                                        } else {
                                            $insertId = $customerId->id;
                                        }

                                        if (empty($customers)) {
                                            if (strpos($row[$keyParentName], "CenterPoint") !== false || strpos($row[$keyParentName], "centerpoint") !== false) {
                                                Account::create([
                                                    'parent_id' => $row[$keyParent],
                                                    'parent_name' => $row[$keyParentName],
                                                    'account_number' => $row[$keyCustomer],
                                                    'account_name' => $row[$keyCustomerName],
                                                    'customer_id' => $insertId,
                                                    'grandparent_id' => $row[$keyGrandParent],
                                                    'category_supplier' => (($fileValue->supplier_id == 7) ? (3) : ($fileValue->supplier_id)) ,
                                                    'grandparent_name' => $row[$keyGrandParentName],
                                                ]);
                                            } else {
                                                Account::create([
                                                    'customer_id' => $insertId,
                                                    'parent_id' => $row[$keyParent],
                                                    'parent_name' => $row[$keyParentName],
                                                    'account_number' => $row[$keyCustomer],
                                                    'account_name' => $row[$keyParentName],
                                                    'grandparent_id' => $row[$keyGrandParent],
                                                    'grandparent_name' => $row[$keyGrandParentName],
                                                    'category_supplier' => (($fileValue->supplier_id == 7) ? (3) : ($fileValue->supplier_id)) ,
                                                ]);
                                            }
                                        } else {
                                            Account::where('account_number', 'LIKE', '%' . ltrim($row[$keyCustomer], '0') . '%')
                                            ->update([
                                                'customer_id' => $insertId,
                                                'parent_id' => $row[$keyParent],
                                                'parent_name' => $row[$keyParentName],
                                                'grandparent_id' => $row[$keyGrandParent],
                                                'grandparent_name' => $row[$keyGrandParentName],
                                                'account_number' => ltrim($row[$keyCustomer], '0'),
                                                'category_supplier' => (($fileValue->supplier_id == 7) ? (3) : ($fileValue->supplier_id)),
                                            ]);
                                        }
                                    }
                                }

                                if (in_array($fileValue->supplier_id, [1, 4, 5, 6]) || count($columnArray[$fileValue->supplier_id]) <= 5) {
                                    $customerId = DB::table('customers')
                                    ->where('customer_name', $row[$keyCustomerName])
                                    ->first();

                                    if (!isset($customerId) && empty($customerId)) {
                                        $insertId = DB::table('customers')
                                        ->insert(['customer_name' => $row[$keyCustomerName]]);
                                    } else {
                                        $insertId = $customerId->id;
                                    }

                                    /** Into case of some supplier which do not have grand parent and parent we will use this 
                                     * condition for insert and update into account table */
                                    if (isset($row[$keyCustomer]) && !empty($row[$keyCustomer])) {
                                        $customers = Account::where('account_number', 'LIKE', '%' . ltrim($row[$keyCustomer], '0') . '%')->first();
                                        if (empty($customers)) {
                                            Account::create([
                                                'customer_id' => $insertId,
                                                'account_number' => ltrim($row[$keyCustomer], '0'),
                                                'category_supplier' => $fileValue->supplier_id,
                                            ]);
                                        } else {
                                            Account::where('account_number', 'LIKE', '%' . ltrim($row[$keyCustomer], '0') . '%')
                                            ->update([
                                                'customer_id' => $insertId,
                                                'account_number' => ltrim($row[$keyCustomer], '0'),
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
                                    /** Here we will getting the customer number key */
                                    if (!empty($columnArray[$fileValue->supplier_id]['customer_number'])) {
                                        $keyCustomerNumber = array_search($columnArray[$fileValue->supplier_id]['customer_number'], $maxNonEmptyValue);
                                    }

                                    /** Here we will getting the cost key */
                                    if (!empty($columnArray[$fileValue->supplier_id]['cost'])) {
                                        $keyAmount = array_search($columnArray[$fileValue->supplier_id]['cost'], $maxNonEmptyValue);
                                    }

                                    /** Here we will getting the invoice number key */
                                    if (!empty($columnArray[$fileValue->supplier_id]['invoice_no'])) {
                                        $keyInvoiceNumber = array_search($columnArray[$fileValue->supplier_id]['invoice_no'], $maxNonEmptyValue);
                                    }

                                    /** Here we will getting the invoice date key */
                                    if (!empty($columnArray[$fileValue->supplier_id]['invoice_date'])) {
                                        $keyInvoiceDate = array_search($columnArray[$fileValue->supplier_id]['invoice_date'], $maxNonEmptyValue);
                                    }

                                    if (isset($keyCustomerNumber) && !empty($row[$keyCustomerNumber])) {
                                        foreach ($row as $key1 => $value) {
                                            if(!empty($maxNonEmptyValue[$key1])) {
                                                /** Creating the excel insert array for supplier table insert using date column conditions */
                                                if ($fileValue->supplier_id != 7) {
                                                    if ($columnArray2[$fileValue->supplier_id][trim($maxNonEmptyValue[$key1])] == $date) {
                                                        $excelInsertArray[$key][$columnArray2[$fileValue->supplier_id][trim($maxNonEmptyValue[$key1])]] =  (!empty($value)) ? Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value))->format('Y-m-d H:i:s') : ('');
                                                    } else {
                                                        if (preg_match('/\bdate\b/i', $maxNonEmptyValue[$key1])  && !empty($value)) { 
                                                            $excelInsertArray[$key][$columnArray2[$fileValue->supplier_id][trim($maxNonEmptyValue[$key1])]] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value))->format('Y-m-d H:i:s');
                                                        } else {
                                                            $excelInsertArray[$key][$columnArray2[$fileValue->supplier_id][trim($maxNonEmptyValue[$key1])]] = $value;
                                                        }
                                                    }
                                                } else {
                                                    $excelInsertArray[$key][$columnArray2[$fileValue->supplier_id][trim($maxNonEmptyValue[$key1])]] = $value;
                                                }

                                                /** In case of od weekly we need to add extra key year  */
                                                if ($fileValue->supplier_id == 7) {
                                                    $excelInsertArray[$key]['year'] = $supplierYear;
                                                }

                                                /** We also need to add attachment id, created_at and updated at keys into the excel insert array */
                                                $excelInsertArray[$key]['attachment_id'] = $fileValue->id;
                                                $excelInsertArray[$key]['created_at'] = Carbon::now()->format('Y-m-d H:i:s');
                                                $excelInsertArray[$key]['updated_at'] = Carbon::now()->format('Y-m-d H:i:s');

                                                /** We also insert data into the order detail table so here we will creating
                                                 * array for insert data into the order details table and also getting the 
                                                 * date columns using regular expression and decode the date code into simple 
                                                 * formate for insert into table
                                                 */
                                                if (preg_match('/\bdate\b/i', $maxNonEmptyValue[$key1]) && !empty($value)) {
                                                    $finalInsertArray[] = [
                                                        'value' => Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value))->format('Y-m-d H:i:s'),
                                                        'supplier_field_id' => (array_search(trim($maxNonEmptyValue[$key1]), $columnArray1) != false) ? (array_search(trim($maxNonEmptyValue[$key1]), $columnArray1)) : (''),
                                                    ];  
                                                } else {
                                                    $finalInsertArray[] = [
                                                        'value' => $value,
                                                        'supplier_field_id' => (array_search(trim($maxNonEmptyValue[$key1]), $columnArray1) != false) ? (array_search(trim($maxNonEmptyValue[$key1]), $columnArray1)) : (''),
                                                    ];  
                                                }
                                            }
                                        }
                                        
                                        if ($fileValue->supplier_id == 7) {
                                            foreach ($weeklyPriceColumnArray as $key => $value) {
                                                if (!empty($row[$key])) {                                                    
                                                    $date = explode("-", $workSheetArray[7][$key]);
                                                    /** Inserting the excel data into the orders table and getting the last 
                                                     * insert id  for further insertion */
                                                    $orderLastInsertId = Order::create([
                                                        'attachment_id' => $fileValue->id,
                                                        'created_by' => $fileValue->created_by,
                                                        'supplier_id' => (($fileValue->supplier_id == 7) ? (3) : ($fileValue->supplier_id)),
                                                        'cost' => str_replace(",", "", number_format($row[$key], 2, '.')),
                                                        'date' =>  (!empty($keyInvoiceDate) && !empty($row[$keyInvoiceDate])) ? (($row[$keyInvoiceDate] && $fileValue->supplier_id == 4) ? (Carbon::createFromFormat('Y-m-d H:i:s', $row[$keyInvoiceDate])->format('Y-m-d H:i:s')) : (Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[$keyInvoiceDate]))->format('Y-m-d H:i:s'))) : (''),
                                                        'invoice_number' => (!empty($keyInvoiceNumber) && !empty($row[$keyInvoiceNumber])) ? ($row[$keyInvoiceNumber]) : (''),
                                                        'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                        'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                        'customer_number' => (!empty($keyCustomerNumber) && !empty($row[$keyCustomerNumber])) ? (ltrim($row[$keyCustomerNumber], "0")) : (''),
                                                    ]);
                                                }
                                            }
                                        } else {
                                            if ($fileValue->supplier_id == 6) {
                                                $customerNumber = explode(" ", $row[$keyCustomerNumber]);
                                                /** Inserting the excel data into the orders table and getting the last 
                                                 * insert id  for further insertion */
                                                $orderLastInsertId = Order::create([
                                                    'attachment_id' => $fileValue->id,
                                                    'created_by' => $fileValue->created_by,
                                                    'supplier_id' => $fileValue->supplier_id,
                                                    'cost' => $row[$keyAmount],
                                                    'invoice_number' => (!empty($keyInvoiceNumber) && !empty($row[$keyInvoiceNumber])) ? ($row[$keyInvoiceNumber]) : (''),
                                                    'date' =>  (isset($keyInvoiceDate) && !empty($row[$keyInvoiceDate])) ? (($row[$keyInvoiceDate] && $fileValue->supplier_id == 4) ? (Carbon::createFromFormat('Y-m-d H:i:s', $row[$keyInvoiceDate])->format('Y-m-d H:i:s')) : (Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[$keyInvoiceDate]))->format('Y-m-d H:i:s'))) : (''),
                                                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                    'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                    'customer_number' => $customerNumber[0],
                                                ]);
                                            } else {
                                                /** Inserting the excel data into the orders table and getting the last 
                                                 * insert id  for further insertion */
                                                $orderLastInsertId = Order::create([
                                                    'attachment_id' => $fileValue->id,
                                                    'created_by' => $fileValue->created_by,
                                                    'supplier_id' => $fileValue->supplier_id,
                                                    'cost' => $row[$keyAmount],
                                                    'invoice_number' => (!empty($keyInvoiceNumber) && !empty($row[$keyInvoiceNumber])) ? ($row[$keyInvoiceNumber]) : (''),
                                                    'date' => (isset($keyInvoiceDate) && !empty($row[$keyInvoiceDate])) ? (Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[$keyInvoiceDate]))->format('Y-m-d H:i:s')) : (''),
                                                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                    'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                    'customer_number' => ltrim($row[$keyCustomerNumber], '0'),
                                                ]);
                                            }
                                        }

                                        /** Adding the order id into the order detail data array */
                                        foreach ($finalInsertArray as &$item) {
                                            if (!isset($item['order_id']) && empty($item['order_id'])) {
                                                $item['order_id'] = $orderLastInsertId->id;
                                            }
                                        }

                                        /** When we create 70 keys array we will insert the array into there spacific table */
                                        if ($count == 70) {
                                            $count = 0;
                                            try {
                                                /** Inserting the data into the spacific supplier table */
                                                DB::table(
                                                    DB::table('supplier_tables')
                                                    ->select('table_name')
                                                    ->where('supplier_id', $fileValue->supplier_id)
                                                    ->first()
                                                    ->table_name
                                                )
                                                ->insert($excelInsertArray);

                                                /** Inserting the data into the order detail table */
                                                DB::table('order_details')->insert($finalInsertArray);
                                            } catch (QueryException $e) {
                                                /** Handling the QueryException using try catch */
                                                Log::error('Error in YourScheduledTask: ' . $e->getMessage());
                                                echo "Database insertion failed: " . $e->getMessage();
                                                echo $e->getTraceAsString();
                                                die;
                                            }
                                            
                                            /** For memory optimization we unset the finalInsertArray and excelInsertArray */
                                            unset($finalInsertArray, $excelInsertArray);
                                        }

                                        /** Using this count variable we will count the array keys
                                         *  how many keys array we created */
                                        $count++; 
                                    }
                                } else {
                                    continue;
                                }
                            }
                        }

                        /** For memory optimization we unset the workSheetArray1 and count */
                        unset($workSheetArray1, $count);
                        
                        if (isset($finalInsertArray) && !empty($finalInsertArray)) {
                            try {
                                /** Updating the file upload status */
                                DB::table('attachments')
                                ->where('id', $fileValue->id)
                                ->update([
                                    'cron' => 5
                                ]);

                                /** Inserting the data into the spacific supplier table */
                                DB::table(
                                    DB::table('supplier_tables')
                                    ->select('table_name')
                                    ->where(
                                        'supplier_id',
                                        $fileValue->supplier_id
                                    )
                                    ->first()
                                    ->table_name
                                )
                                ->insert($excelInsertArray);

                                /** Inserting the data into the order detail table */
                                DB::table('order_details')
                                ->insert($finalInsertArray);
                            } catch (QueryException $e) {
                                /** Handling the QueryException using try catch */
                                Log::error('Error in YourScheduledTask: ' . $e->getMessage());
                                echo "Database insertion failed: " . $e->getMessage();
                            }
                        }

                        /** For memory optimization we unset the finalInsertArray and excelInsertArray */
                        unset($finalInsertArray, $excelInsertArray);
                    }
                    try {
                        /** Update the 'cron' field six after processing done */
                        DB::table('attachments')->where('id', $fileValue->id)->update(['cron' => 6]);

                        $this->info('Uploaded files processed successfully.');
                    } catch (QueryException $e) {
                        /** Handling the QueryException using try catch */
                        Log::error('Database updation failed: ' . $e->getMessage());
                        echo "Database updation failed: " . $e->getMessage();
                        die;
                    }
                } catch (\Exception $e) {
                    /** Handling the Exception using try catch */
                    Log::error('Exception loading spreadsheet: ' . $e->getMessage());
                    echo "Error loading spreadsheet: " . $e->getMessage();
                }
            } else {
                echo "No file left to process.";
            }
        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
            /** Handling the Exception using try catch */
            Log::error('Error loading spreadsheet: ' . $e->getMessage());
            echo "Error loading spreadsheet: " . $e->getMessage();
            die;
        } catch (QueryException $e) {
            /** Handling the QueryException using try catch */
            Log::error('Database table attachments select query failed: ' . $e->getMessage());
            echo "Database table attachments select query failed: " . $e->getMessage();
            die;
        }  
    }
}
