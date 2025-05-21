<?php

namespace App\Console\Commands;

use App\Http\Controllers\AccountController;
use Illuminate\Support\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\{DB, File, Log, Mail};
use PhpOffice\PhpSpreadsheet\Reader\{Xls, Xlsx};
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use App\Models\{Order, Account, UploadedFiles, ManageColumns};
use DateTime;

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
        $userFileExist = DB::table('consolidated_file')
            ->select('id', 'file_name')
            ->where('delete_check', 1)
            ->get();

        if ($userFileExist->isNotEmpty()) {
            foreach ($userFileExist as $key => $value) {
                if (File::exists(storage_path('app/' . $value->file_name))) {
                    try {
                        File::delete(storage_path('app/' . $value->file_name));
                        
                        /** Delete the database record after the file deletion */
                        DB::table('consolidated_file')
                            ->where('id', $value->id)
                            ->delete();
                    } catch (\Exception $e) {
                        Log::error('Error deleting file: ' . $e->getMessage());
                    }
                } else {
                    session()->flash('error', 'File not found.');
                }
            }
        }

        /** This is the folder path where we save the file */
        $destinationPath = public_path('/excel_sheets');

        try {
            /** Select those file name where re_upload is 1 */
            $fileValue = DB::table('attachments')
                ->select('id', 'supplier_id', 'file_name', 'created_by', 'conversion_rate')
                ->where('re_upload', '=', 1)
                ->whereNull('deleted_by')
                ->first();

            if ($fileValue !== null) {
                $reUpload = true;
            } else {
                $reUpload = false;
            }

            /** Select those file name where cron is 11 */
            if ($fileValue == null) {
                $fileValue = DB::table('attachments')
                    ->select('id', 'supplier_id', 'file_name', 'created_by', 'conversion_rate')
                    ->where('cron', '=', 11)
                    ->whereNull('deleted_by')
                    ->first();
            }
            
            $suppliers = ManageColumns::getRequiredColumns();

            if ($fileValue !== null && $fileValue->supplier_id != 15) {
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

                    /** Run the for loop for excel sheets */
                    for ($i = 0; $i <= $sheetCount; $i++) {
                        $count = $maxNonEmptyCount = 0;

                        if (($sheetCount == 1 && $i == 1 && $fileValue->supplier_id != 5) || ($fileValue->supplier_id == 5 && $i == 0) || ($fileValue->supplier_id == 7 && $i != 2)) {
                            continue;
                        }

                        DB::table('attachments')
                        ->where('id', $fileValue->id)
                        ->update([
                            'cron' => 4
                        ]);

                        $workSheetArray = $spreadSheet->getSheet($i)->toArray(); /** Getting worksheet using index */

                        foreach ($workSheetArray as $key=>$value) {
                            $finalExcelKeyArray1 = array_values(array_filter($value, function ($item) {
                                        return !empty($item);
                                    },
                                    ARRAY_FILTER_USE_BOTH
                                )
                            );
                                        
                            /** Clean up the values */
                            $cleanedArray = array_map(function ($values) {
                                /** Remove line breaks and trim whitespace */
                                return trim(str_replace(["\r", "\n"], '', $values));
                            }, $finalExcelKeyArray1);
    
                            if (isset($suppliers[$fileValue->supplier_id])) {
                                $supplierValues = $suppliers[$fileValue->supplier_id];
                                if ($fileValue->supplier_id == 7) {
                                    $supplierValues = array_slice($supplierValues, 0, 6, true);                        
                                    if (isset($cleanedArray[5]) && $cleanedArray[5] == $supplierValues[5]) {
                                        foreach ($cleanedArray as $keys => $valuess) {
                                            if ($keys > 5) {
                                                $cleanedArray[$keys] = trim("year_" . substr($cleanedArray[$keys], - 2));
                                            }
                                        }
                                    } else {
                                        continue;
                                    }
                                }

                                $arrayDiff = array_diff($supplierValues, $cleanedArray);

                                if (empty($arrayDiff)) {
                                    $maxNonEmptyValue = $value;
                                    $startIndexValueArray = $key;
                                    break;
                                }
                            }
                        }

                        if (!isset($maxNonEmptyValue)) {
                            continue;
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

                        if ($fileValue->supplier_id == 14) {
                            $columnArray2[$fileValue->supplier_id]["Transaction Source System1"] = 'transaction_source_system';
                            $columnArray2[$fileValue->supplier_id]["Payment Method Code1"] = 'payment_method_code';
                            $maxNonEmptyValue[38] = "Payment Method Code1";
                            $maxNonEmptyValue[39] = "Payment Method Code";
                            $maxNonEmptyValue[44] = "Transaction Source System1";
                            $maxNonEmptyValue[45] = "Transaction Source System";
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

                                if ($fileValue->supplier_id == 14) {
                                    /** Check if 'Group ID', 'Payment Method Code' and 'Transaction Source System' exists in the array */
                                    $paymentMethodCodeIndex = array_search('Payment Method Code', $cleanedArray);
                                    $transactionSourceSystemIndex = array_search('Transaction Source System', $cleanedArray);
        
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
                            $supplierYear = substr($maxNonEmptyValue[6], 0, 4);
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

                                        if (!$customers) {
                                            $data = DB::table('customers')
                                                ->where('customer_name', $row[$keyCustomerName])
                                                ->first();

                                            if (!$data) {
                                                $insertId = DB::table('customers')
                                                ->insertGetId([
                                                    'customer_name' => $row[$keyCustomerName]
                                                ]);
                                            } else {
                                                $insertId = $data->id;
                                            }
                                            
                                            DB::table('customer_suppliers')
                                            ->insert([
                                                'customer_id' => $insertId,
                                                'supplier_id' => $fileValue->supplier_id,
                                            ]);

                                            if (strpos($row[$keyParentName], "CenterPoint") !== false || strpos($row[$keyParentName], "centerpoint") !== false) {
                                                Account::create([
                                                    'parent_id' => (!empty($keyParent)) ? ($row[$keyParent]) : (''),
                                                    'parent_name' => (!empty($keyParentName)) ? ($row[$keyParentName]) : (''),
                                                    'account_number' => (!empty($keyCustomer)) ? ($row[$keyCustomer]) : (''),
                                                    'account_name' => (!empty($keyCustomerName)) ? ($row[$keyCustomerName]) : (''),
                                                    'customer_id' => $insertId,
                                                    'grandparent_id' => (!empty($keyGrandParent)) ? ($row[$keyGrandParent]) : (''),
                                                    'supplier_id' => (($fileValue->supplier_id == 7) ? (3) : ($fileValue->supplier_id)) ,
                                                    'grandparent_name' => (!empty($keyGrandParentName)) ? ($row[$keyGrandParentName]) : (''),
                                                ]);
                                            } else {
                                                Account::create([
                                                    'customer_id' => $insertId,
                                                    'parent_id' => (!empty($keyParent)) ? ($row[$keyParent]) : (''),
                                                    'parent_name' => (!empty($keyParentName)) ? ($row[$keyParentName]) : (''),
                                                    'account_number' => (!empty($keyCustomer)) ? ($row[$keyCustomer]) : (''),
                                                    'account_name' => (!empty($keyParentName)) ? ($row[$keyParentName]) : (''),
                                                    'grandparent_id' => (!empty($keyGrandParent)) ? ($row[$keyGrandParent]) : (''),
                                                    'grandparent_name' => (!empty($keyGrandParentName)) ? ($row[$keyGrandParentName]) : (''),
                                                    'supplier_id' => (($fileValue->supplier_id == 7) ? (3) : ($fileValue->supplier_id)) ,
                                                ]);
                                            }
                                        } else {
                                            $insertId = $customers->customer_id;
                                            
                                            $customerSupplierCheck = DB::table('customer_suppliers')
                                            ->where([
                                                'customer_id' => $insertId,
                                                'supplier_id' => $fileValue->supplier_id,
                                            ])
                                            ->first();
                                            
                                            if (!$customerSupplierCheck) {
                                                DB::table('customer_suppliers')
                                                ->insert([
                                                    'customer_id' => $insertId,
                                                    'supplier_id' => $fileValue->supplier_id,
                                                ]);
                                            }

                                            $data = [
                                                'customer_id' => $insertId,
                                                'parent_id' => (!empty($keyParent)) ? ($row[$keyParent]) : (''),
                                                'parent_name' => (!empty($keyParentName)) ? ($row[$keyParentName]) : (''),
                                                'grandparent_id' => (!empty($keyGrandParent)) ? ($row[$keyGrandParent]) : (''),
                                                'grandparent_name' => (!empty($keyGrandParentName)) ? ($row[$keyGrandParentName]) : (''),
                                                'account_number' => ltrim($row[$keyCustomer], '0'),
                                                'supplier_id' => (($fileValue->supplier_id == 7) ? (3) : ($fileValue->supplier_id)),
                                            ];
                                            
                                            /** Remove empty values */
                                            $accountData = array_filter($data, function ($value) {
                                                return $value !== '' && $value !== null;
                                            });

                                            Account::where('id', $customers->id)
                                            ->update($accountData);
                                            $accountData = [];
                                        }
                                    }
                                }

                                if (!in_array($fileValue->supplier_id, [2, 3, 7]) || count($columnArray[$fileValue->supplier_id]) <= 5) {
                                    /** Into case of some supplier which do not have grand parent and parent we will use this 
                                     * condition for insert and update into account table */
                                    if (isset($row[$keyCustomer]) && !empty($row[$keyCustomer])) {
                                        $customers = Account::where('account_number', 'LIKE', '%' . ltrim($row[$keyCustomer], '0') . '%')->first();
                                        if (!$customers) {
                                            $data = DB::table('customers')
                                            ->where('customer_name', $row[$keyCustomerName])
                                            ->first();

                                            if (!$data) {
                                                $insertId = DB::table('customers')
                                                ->insertGetId([
                                                    'customer_name' => $row[$keyCustomerName]
                                                ]);
                                            } else {
                                                $insertId = $data->id;
                                            }

                                            DB::table('customer_suppliers')
                                            ->insert([
                                                'customer_id' => $insertId,
                                                'supplier_id' => $fileValue->supplier_id,
                                            ]);

                                            Account::create([
                                                'customer_id' => $insertId,
                                                'account_number' => ltrim($row[$keyCustomer], '0'),
                                                'supplier_id' => $fileValue->supplier_id,
                                            ]);
                                        } else {
                                            $insertId = $customers->customer_id;
                                            $customerSupplierCheck = DB::table('customer_suppliers')
                                            ->where([
                                                'customer_id' => $insertId,
                                                'supplier_id' => $fileValue->supplier_id,
                                            ])
                                            ->first();
                                            
                                            if (!$customerSupplierCheck) {
                                                DB::table('customer_suppliers')
                                                ->insert([
                                                    'customer_id' => $insertId,
                                                    'supplier_id' => $fileValue->supplier_id,
                                                ]);
                                            }

                                            $data = [
                                                'customer_id' => $insertId,
                                                'account_number' => ltrim($row[$keyCustomer], '0'),
                                                'supplier_id' => $fileValue->supplier_id,
                                            ];
                                            
                                            /** Remove empty values */
                                            $accountData = array_filter($data, function ($value) {
                                                return $value !== '' && $value !== null;
                                            });

                                            // Account::where('account_number', 'LIKE', '%' . ltrim($row[$keyCustomer], '0') . '%')
                                            Account::where('id', $customers->id)
                                            ->update($accountData);

                                            $accountData = [];
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
                                                if (isset($columnArray2[$fileValue->supplier_id][trim($maxNonEmptyValue[$key1])]) || $fileValue->supplier_id == 7) {
                                                    /** Creating the excel insert array for supplier table insert using date column conditions */
                                                    if ($fileValue->supplier_id != 7) {
                                                        if ($columnArray2[$fileValue->supplier_id][trim($maxNonEmptyValue[$key1])] == $date) {
                                                            if (str_contains($value, '/')) {
                                                                $excelInsertArray[$key][$columnArray2[$fileValue->supplier_id][trim($maxNonEmptyValue[$key1])]] = (!empty($value)) ? DateTime::createFromFormat('d/m/Y', $value)->format('Y-m-d H:i:s') : ('');
                                                            } else {
                                                                $excelInsertArray[$key][$columnArray2[$fileValue->supplier_id][trim($maxNonEmptyValue[$key1])]] =  (!empty($value)) ? Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value))->format('Y-m-d H:i:s') : ('');
                                                            }
                                                        } else {
                                                            if (preg_match('/\bdate\b/i', $maxNonEmptyValue[$key1])  && !empty($value)) {
                                                                if (str_contains($value, '/')) {
                                                                    $excelInsertArray[$key][$columnArray2[$fileValue->supplier_id][trim($maxNonEmptyValue[$key1])]] = DateTime::createFromFormat('d/m/Y', $value)->format('Y-m-d H:i:s');
                                                                } else {
                                                                    $excelInsertArray[$key][$columnArray2[$fileValue->supplier_id][trim($maxNonEmptyValue[$key1])]] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value))->format('Y-m-d H:i:s');
                                                                } 
                                                                
                                                            } else {
                                                                $excelInsertArray[$key][$columnArray2[$fileValue->supplier_id][trim($maxNonEmptyValue[$key1])]] = $value;
                                                            }
                                                        }
                                                    } else {
                                                        $excelInsertArray[$key][$columnArray2[$fileValue->supplier_id][trim($maxNonEmptyValue[$key1])]] = $value;
                                                    }
                                                }
                                                
                                                /** In case of od weekly we need to add extra key year  */
                                                if ($fileValue->supplier_id == 7) {
                                                    $excelInsertArray[$key]['year'] = $supplierYear;
                                                }

                                                /** We also need to add attachment id, converted_price, created_at and updated at keys into the excel insert array */
                                                if ($fileValue->supplier_id == 6) {
                                                    if (!empty($fileValue->conversion_rate) && isset($row[$keyAmount])) {
                                                        $amount = $row[$keyAmount];
                                                        $newKey = $keyAmount + 3;
                                                        $secondAmount = $row[$newKey];

                                                        $convertedPrice = (float) $fileValue->conversion_rate; /** Cast to float */
                                                        $calculatedAmount = round($amount * $convertedPrice, 2); /** Perform the calculation */
                                                        $calculatedAmount2 = round($secondAmount * $convertedPrice, 2); /** Perform the calculation */
                                                        
                                                        $excelInsertArray[$key]['converted_sales_amount_p'] = $calculatedAmount;
                                                        $excelInsertArray[$key]['converted_avg_selling_price_p'] = $calculatedAmount2;
                                                        // unset($amount, $secondAmount);
                                                    } else {
                                                        $excelInsertArray[$key]['converted_sales_amount_p'] = 0;
                                                        $excelInsertArray[$key]['converted_avg_selling_price_p'] = 0;
                                                       
                                                    }
                                                }

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
                                                        'value' => (isset($value) && !empty($value)) ? ((str_contains($value, '/')) ? (DateTime::createFromFormat('d/m/Y', $value)->format('Y-m-d H:i:s')) : (Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value))->format('Y-m-d H:i:s'))) : (''),
                                                        'attachment_id' => $fileValue->id,
                                                        'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                        // 'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                        'supplier_field_id' => (array_search(trim($maxNonEmptyValue[$key1]), $columnArray1) != false) ? (array_search(trim($maxNonEmptyValue[$key1]), $columnArray1)) : (''),
                                                    ];  
                                                } else {
                                                    $finalInsertArray[] = [
                                                        'value' => $value,
                                                        'attachment_id' => $fileValue->id,
                                                        'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                        // 'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                        'supplier_field_id' => (array_search(trim($maxNonEmptyValue[$key1]), $columnArray1) != false) ? (array_search(trim($maxNonEmptyValue[$key1]), $columnArray1)) : (''),
                                                    ];  
                                                }
                                            }
                                        }
                                        
                                        if ($fileValue->supplier_id == 7) {
                                            foreach ($weeklyPriceColumnArray as $key => $value) {
                                                if (!empty($row[$key])) {                                                    
                                                    $date = explode("-", $workSheetArray[6][$key]);
                                                    
                                                    /** Inserting the excel data into the orders table and getting the last 
                                                     * insert id  for further insertion */
                                                    $orderLastInsertId = Order::create([
                                                        'attachment_id' => $fileValue->id,
                                                        'created_by' => $fileValue->created_by,
                                                        'supplier_id' => (($fileValue->supplier_id == 7) ? (3) : ($fileValue->supplier_id)),
                                                        'cost' => str_replace(",", "", number_format($row[$key], 2, '.')),
                                                        'date' =>  (!empty($keyInvoiceDate) && !empty($row[$keyInvoiceDate])) ? (Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[$keyInvoiceDate]))->format('Y-m-d H:i:s')) : (''),
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
                                                $convertedPrice = (float) $fileValue->conversion_rate; /** Cast to float */

                                                /** Inserting the excel data into the orders table and getting the last 
                                                 * insert id  for further insertion */
                                                $orderLastInsertId = Order::create([
                                                    'attachment_id' => $fileValue->id,
                                                    'created_by' => $fileValue->created_by,
                                                    'supplier_id' => $fileValue->supplier_id,
                                                    'cost' => round($row[$keyAmount] * $convertedPrice, 2),
                                                    'invoice_number' => (!empty($keyInvoiceNumber) && !empty($row[$keyInvoiceNumber])) ? ($row[$keyInvoiceNumber]) : (''),
                                                    'date' =>  (!empty($keyInvoiceDate) && !empty($row[$keyInvoiceDate])) ? ((str_contains($row[$keyInvoiceDate], '/')) ? (DateTime::createFromFormat('d/m/Y', $row[$keyInvoiceDate])->format('Y-m-d H:i:s')) : (Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[$keyInvoiceDate]))->format('Y-m-d H:i:s'))) : (''),
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
                    }

                    try {
                        /** Update the 'cron' field six after processing done */
                        if ($reUpload) {
                            DB::table('attachments')
                            ->where('id', $fileValue->id)
                            ->update([
                                'cron' => 6,
                                're_upload' => 0,
                            ]);
                        } else {
                            DB::table('attachments')
                            ->where('id', $fileValue->id)
                            ->update(['cron' => 6]);
                        }

                        if ($fileValue->supplier_id == 7) {
                            $existAttachments = DB::table('odp_attachments')
                            ->where('attachment_id', $fileValue->id)
                            ->first();
                            
                            if (!$existAttachments) {
                                DB::table('odp_attachments')
                                ->insert([
                                    'year' => $supplierYear,
                                    'attachment_id' => $fileValue->id,
                                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                    'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
                                ]);
                            }
                        }

                        /** We use try catch to handle errors during email send */
                        try {
                            Log::info('Attempting to send email...');
                            echo "Attempting to send email...";

                            $countAccount = Account::whereNull('account_name')
                            ->orWhere('account_name', '')
                            ->count();
                            
                            if ($countAccount > 0) {
                                /** Setting the email where we want to send email */
                                $emails = [
                                    'vishustaple.in@gmail.com',
                                    'anurag@centerpointgroup.com',
                                    'santosh@centerpointgroup.com',
                                    'mgaballa@centerpointgroup.com',
                                ];
                                // $emails = [
                                //     'vishustaple.in@gmail.com',
                                //     'kekohokubri-2165@yopmail.com',
                                // ];
                    
                                $data = [
                                    'link' => url('admin/accounts/customer-edit'),
                                    'body' => 'A new account has been added to the database. Please check the link below.',
                                ];
                    
                                /** Sending email here */
                                Mail::send('mail.newaccount', $data, function($message) use ($emails) {
                                    $message->to($emails)
                                            ->subject('New Account in Database');
                                });
                    
                                echo "Email sent successfully";
                                Log::info('Email sent successfully');
                            }
                        } catch (\Exception $e) {
                            /** Handle the exception here */
                            Log::error('Email sending failed: ' . $e->getMessage());
                            echo "Email sending failed: " . $e->getMessage();
                        }

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
                    die;
                }
            } elseif ($fileValue !== null && $fileValue->supplier_id == 15) {
                $supplierTableName = DB::table('supplier_tables')
                ->select('table_name')
                ->where('supplier_id', $fileValue->supplier_id)
                ->first()
                ->table_name;

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

                $columnArray2 = [];
                foreach ($columnValues as $key => $value) {
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
                    
                    /** Run the for loop for excel sheets */
                    for ($i = 0; $i < $sheetCount; $i++) {
                        $count = $maxNonEmptyCount = 0;

                        DB::table('attachments')
                        ->where('id', $fileValue->id)
                        ->update([
                            'cron' => 4
                        ]);

                        $workSheetArray = $spreadSheet->getSheet($i)->toArray(); /** Getting worksheet using index */

                        foreach ($workSheetArray as $key=>$value) {
                            $finalExcelKeyArray1 = array_values(array_filter($value, function ($item) {
                                        return !empty($item);
                                    },
                                    ARRAY_FILTER_USE_BOTH
                                )
                            );
                                        
                            /** Clean up the values */
                            $cleanedArray = array_map(function ($values) {
                                /** Remove line breaks and trim whitespace */
                                return trim(str_replace(["\r", "\n"], '', $values));
                            }, $finalExcelKeyArray1);
    
                            if (isset($suppliers[$fileValue->supplier_id])) {
                                $supplierValues = $suppliers[$fileValue->supplier_id];
                                $arrayDiff = array_diff($supplierValues, $cleanedArray);

                                if (empty($arrayDiff)) {
                                    $maxNonEmptyValue = $value;
                                    $startIndexValueArray = $key;
                                    break;
                                }
                            }
                        }

                        if (!isset($maxNonEmptyValue)) {
                            continue;
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

                                /** Calculate the difference between the $supplierValues array and the $cleanedArray */
                                /** This returns an array containing all elements from $supplierValues that are not present in $cleanedArray */
                                $arrayDiff = array_diff($supplierValues, $cleanedArray);

                                if (empty($arrayDiff)) {
                                    $maxNonEmptyValue = $value;
                                    $startIndexValueArray = $key;
                                    break;
                                }
                            }
                        }

                        if (!isset($maxNonEmptyValue)) {
                            continue;
                        }

                        /** Clean up the values */
                        $maxNonEmptyValue = array_map(function ($value) {
                            /** Remove line breaks and trim whitespace */
                            return str_replace(["\r", "\n"], '', $value);
                        }, $maxNonEmptyValue);

                        /** Unset the "$maxNonEmptyCount" for memory save */
                        unset($maxNonEmptyCount);

                        $startIndex = $startIndexValueArray; /** Specify the starting index for get the excel column value */

                        /** Unset the "$startIndexValueArray" for memory save */
                        unset($startIndexValueArray);

                        if (isset($workSheetArray) && !empty($workSheetArray)) {
                            /** For insert data into the database */
                            foreach ($workSheetArray as $key => $row) {
                                if ($key > $startIndex) {
                                    foreach ($row as $key1 => $value) {
                                        if(!empty($maxNonEmptyValue[$key1])) {
                                            if (isset($columnArray2[$fileValue->supplier_id][trim($maxNonEmptyValue[$key1])])) {
                                                /** Creating the excel insert array for supplier table insert using date column conditions */
                                                $excelInsertArray[$key][$columnArray2[$fileValue->supplier_id][trim($maxNonEmptyValue[$key1])]] = $value;
                                            }

                                            /** We also need to add attachment id, created_at and updated at keys into the excel insert array */
                                            $excelInsertArray[$key]['attachment_id'] = $fileValue->id;
                                            $excelInsertArray[$key]['created_at'] = Carbon::now()->format('Y-m-d H:i:s');
                                            $excelInsertArray[$key]['updated_at'] = Carbon::now()->format('Y-m-d H:i:s');
                                        }
                                    }

                                    /** When we create 70 keys array we will insert the array into there spacific table */
                                    if ($count == 70) {
                                        $count = 0;
                                        try {
                                            /** Inserting the data into the spacific supplier table */
                                            foreach ($excelInsertArray as $key => $value) {
                                                if (!empty(trim($value['invoice_number']))) {
                                                    $recordExist = DB::table($supplierTableName)
                                                    ->where('invoice_number', 'LIKE', trim($value['invoice_number']))
                                                    ->first();

                                                    if (!$recordExist) {
                                                        DB::table($supplierTableName)
                                                        ->insert($value);
                                                    }
                                                }
                                            }
                                        } catch (QueryException $e) {
                                            /** Handling the QueryException using try catch */
                                            Log::error('Error in YourScheduledTask: ' . $e->getMessage());
                                            echo "Database insertion failed: " . $e->getMessage();
                                            echo $e->getTraceAsString();
                                            die;
                                        }
                                        
                                        /** For memory optimization we unset the excelInsertArray */
                                        unset($excelInsertArray);
                                    }

                                    /** Using this count variable we will count the array keys
                                     *  how many keys array we created */
                                    $count++;
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
                                    foreach ($excelInsertArray as $key => $value) {
                                        if (!empty(trim($value['invoice_number']))) {
                                            $recordExist = DB::table($supplierTableName)
                                            ->where('invoice_number', 'LIKE', trim($value['invoice_number']))
                                            ->first();

                                            if (!$recordExist) {
                                                DB::table($supplierTableName)
                                                ->insert($value);
                                            }
                                        }
                                    }
                                } catch (QueryException $e) {
                                    /** Handling the QueryException using try catch */
                                    Log::error('Error in YourScheduledTask: ' . $e->getMessage());
                                    echo "Database insertion failed: " . $e->getMessage();
                                }
                            }

                            /** For memory optimization we unset the excelInsertArray */
                            unset($excelInsertArray);
                        }
                    }

                    try {
                        /** Update the 'cron' field six after processing done */
                        DB::table('attachments')
                        ->where('id', $fileValue->id)
                        ->update(['cron' => 6]);

                        /** We use try catch to handle errors during email send */
                        try {
                            Log::info('Attempting to send email...');
                            echo "Attempting to send email...";
                            $countAccount = Account::whereNull('account_name')
                            ->orWhere('account_name', '')
                            ->count();
                            
                            if ($countAccount > 0) {
                                /** Setting the email where we want to send email */
                                $emails = [
                                    'vishustaple.in@gmail.com',
                                    'anurag@centerpointgroup.com',
                                    'santosh@centerpointgroup.com',
                                    'mgaballa@centerpointgroup.com',
                                ];
                                // $emails = [
                                //     'vishustaple.in@gmail.com',
                                //     'kekohokubri-2165@yopmail.com',
                                // ];
                    
                                $data = [
                                    'link' => url('admin/accounts/customer-edit'),
                                    'body' => 'A new account has been added to the database. Please check the link below.',
                                ];
                    
                                /** Sending email here */
                                Mail::send('mail.newaccount', $data, function($message) use ($emails) {
                                    $message->to($emails)
                                            ->subject('New Account in Database');
                                });
                    
                                echo "Email sent successfully";
                                Log::info('Email sent successfully');
                            }
                        } catch (\Exception $e) {
                            /** Handle the exception here */
                            Log::error('Email sending failed: ' . $e->getMessage());
                            echo "Email sending failed: " . $e->getMessage();
                        }

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
                    die;
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
