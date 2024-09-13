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
            ->select('id', 'supplier_id', 'file_name', 'start_date', 'end_date', 'created_by')
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
                    'supplier_fields.label as raw_label',
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
                foreach ($columnValues as $key => $value) {
                    if (!empty($value->required_field_column)) {
                        $columnArray[$value->supplier_id][$value->required_field_column] = $value->raw_label;
                    }

                    if ($value->supplier_id == 7) {
                        $columnArray[$value->supplier_id]['cost'] = '';
                        $columnArray[$value->supplier_id]['invoice_date'] = '';
                    }

                    if ($value->required_field_id == 9) {
                        $date = preg_replace('/^_+|_+$/', '', strtolower(preg_replace('/[^A-Za-z0-9_]/', '', str_replace(' ', '_', $value->raw_label))));
                    }

                    if (in_array($value->supplier_id, [1, 7])) {
                        $columnArray[$value->supplier_id]['invoice_no'] = '';
                    }

                    $columnArray1[$value->id] = $value->raw_label;
                }

                if ($fileValue->supplier_id == 7) {
                    $columnArray2 = [
                        $fileValue->supplier_id => [
                            $columnArray1['199'] => 'gp_id',
                            $columnArray1['200'] => 'gp_name',
                            $columnArray1['201'] => 'parent_id',
                            $columnArray1['202'] => 'parent_name',
                            $columnArray1['203'] => 'account_id',
                            $columnArray1['204'] => 'account_name',
                            $columnArray1['205'] => 'year_01',
                            $columnArray1['206'] => 'year_02',
                            $columnArray1['207'] => 'year_03',
                            $columnArray1['208'] => 'year_04',
                            $columnArray1['209'] => 'year_05',
                            $columnArray1['210'] => 'year_06',
                            $columnArray1['211'] => 'year_07',
                            $columnArray1['212'] => 'year_08',
                            $columnArray1['213'] => 'year_09',
                            $columnArray1['214'] => 'year_10',
                            $columnArray1['215'] => 'year_11',
                            $columnArray1['216'] => 'year_12',
                            $columnArray1['217'] => 'year_13',
                            $columnArray1['218'] => 'year_14',
                            $columnArray1['219'] => 'year_15',
                            $columnArray1['220'] => 'year_16',
                            $columnArray1['221'] => 'year_17',
                            $columnArray1['222'] => 'year_18',
                            $columnArray1['223'] => 'year_19',
                            $columnArray1['224'] => 'year_20',
                            $columnArray1['225'] => 'year_21',
                            $columnArray1['226'] => 'year_22',
                            $columnArray1['227'] => 'year_23',
                            $columnArray1['228'] => 'year_24',
                            $columnArray1['229'] => 'year_25',
                            $columnArray1['230'] => 'year_26',
                            $columnArray1['231'] => 'year_27',
                            $columnArray1['232'] => 'year_28',
                            $columnArray1['233'] => 'year_29',
                            $columnArray1['234'] => 'year_30',
                            $columnArray1['235'] => 'year_31',
                            $columnArray1['236'] => 'year_32',
                            $columnArray1['237'] => 'year_33',
                            $columnArray1['238'] => 'year_34',
                            $columnArray1['239'] => 'year_35',
                            $columnArray1['240'] => 'year_36',
                            $columnArray1['242'] => 'year_37',
                            $columnArray1['243'] => 'year_38',
                            $columnArray1['244'] => 'year_39',
                            $columnArray1['245'] => 'year_40',
                            $columnArray1['246'] => 'year_41',
                            $columnArray1['247'] => 'year_42',
                            $columnArray1['248'] => 'year_43',
                            $columnArray1['249'] => 'year_44',
                            $columnArray1['250'] => 'year_45',
                            $columnArray1['251'] => 'year_46',
                            $columnArray1['252'] => 'year_47',
                            $columnArray1['253'] => 'year_48',
                            $columnArray1['254'] => 'year_49',
                            $columnArray1['255'] => 'year_50',
                            $columnArray1['256'] => 'year_51',
                            $columnArray1['257'] => 'year_52',
                            // $columnArray1['611'] => 'year_53',
                            $columnArray1['537'] => 'year_53',
                        ]
                    ];
                } else {
                    $columnArray2 = [];
                    foreach ($columnArray1 as $key => $value) {
                        $columnArray2[$fileValue->supplier_id][$value] = preg_replace('/^_+|_+$/', '', strtolower(preg_replace('/[^A-Za-z0-9_]/', '', str_replace(' ', '_', $value)))); 
                    }
                }

                try {
                    /** Increasing the memory limit becouse memory limit issue */
                    ini_set('memory_limit', '1024M');

                    /** Inserting files data into the database after doing excel import */                       
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

                        DB::table('attachments')
                        ->where('id', $fileValue->id)
                        ->update([
                            'cron' => 4
                        ]);

                        for ($i = 0; $i <= $sheetCount; $i++) {
                            $count = $maxNonEmptyCount = 0;
                            
                            // if ($fileValue->supplier_id == 5 && $i == 1) {
                            //     continue;
                            // }
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
        
                                if ($fileValue->supplier_id == 7) {
                                    foreach ($cleanedArray as $keys => $valuess) {
                                        if ($keys > 5) {
                                            $cleanedArray[$keys] = trim("year_" . substr($cleanedArray[$keys], - 2));
                                        }
                                    }
                                }
        
                                if (isset($suppliers[$fileValue->supplier_id])) {
                                    $supplierValues = $suppliers[$fileValue->supplier_id];

                                    if ($fileValue->supplier_id == 7) {
                                        $supplierValues = array_slice($supplierValues, 0, 6, true);
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
                            
                            if ($fileValue->supplier_id == 7) {
                                $supplierYear = substr($maxNonEmptyValue[7], 0, 4);
                                if (!empty($supplierYear)) {
                                    $dataIdForDeleteDuplicateData = DB::table(DB::table('supplier_tables')->select('table_name')->where('supplier_id', $fileValue->supplier_id)->first()->table_name)->where('year', $supplierYear)->select('attachment_id')->first();
                                    if (isset($dataIdForDeleteDuplicateData->attachment_id) && !empty($dataIdForDeleteDuplicateData->attachment_id)) {
                                        DB::table(DB::table('supplier_tables')->select('table_name')->where('supplier_id', $fileValue->supplier_id)->first()->table_name)->where('year', $supplierYear)->delete();
                                        DB::table('order_details')->where('attachment_id', $dataIdForDeleteDuplicateData->attachment_id)->delete();
                                        DB::table('orders')->where('attachment_id', $dataIdForDeleteDuplicateData->attachment_id)->delete();
                                    }
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
                                if ($key > $startIndex) {
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
                                                if (strpos($row[$keyParentName], "CenterPoint") !== false || strpos($row[$keyParentName], "centerpoint") !== false) {
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
                                                        'account_name' => $row[$keyParentName],
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
                                                            if (preg_match('/\bdate\b/i', $maxNonEmptyValue[$key1])  && !empty($value)) { 
                                                                $excelInsertArray[$key][$columnArray2[$fileValue->supplier_id][trim($maxNonEmptyValue[$key1])]] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value))->format('Y-m-d H:i:s');
                                                            } else {
                                                                $excelInsertArray[$key][$columnArray2[$fileValue->supplier_id][trim($maxNonEmptyValue[$key1])]] = $value;
                                                            }
                                                        }
                                                    } else {
                                                        if ($key1 < 6) {
                                                            $excelInsertArray[$key][$columnArray2[$fileValue->supplier_id][trim($maxNonEmptyValue[$key1])]] = $value;
                                                        } else {
                                                            $excelInsertArray[$key][$columnArray2[$fileValue->supplier_id][trim("year_" . substr($maxNonEmptyValue[$key1], - 2))]] = $value;
                                                        }
                                                    }
                                                    
                                                    if ($fileValue->supplier_id == 7) {
                                                        $excelInsertArray[$key]['year'] = $supplierYear;
                                                    }

                                                    $excelInsertArray[$key]['attachment_id'] = $fileValue->id;
                                                    $excelInsertArray[$key]['created_at'] = Carbon::now()->format('Y-m-d H:i:s');
                                                    $excelInsertArray[$key]['updated_at'] = Carbon::now()->format('Y-m-d H:i:s');

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
    
                                                        $orderLastInsertId = Order::create([
                                                            'attachment_id' => $fileValue->id,
                                                            'created_by' => $fileValue->created_by,
                                                            'supplier_id' => (($fileValue->supplier_id == 7) ? (3) : ($fileValue->supplier_id)),
                                                            'cost' => str_replace(",", "", number_format($row[$key], 2, '.')),
                                                            'date' =>  (!empty($keyInvoiceDate) && !empty($row[$keyInvoiceDate])) ? (($row[$keyInvoiceDate] && $fileValue->supplier_id == 4) ? (Carbon::createFromFormat('Y-m-d H:i:s', $row[$keyInvoiceDate])->format('Y-m-d H:i:s')) : (Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[$keyInvoiceDate]))->format('Y-m-d H:i:s'))) : ($fileValue->start_date),
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
                                                    $orderLastInsertId = Order::create([
                                                        'attachment_id' => $fileValue->id,
                                                        'created_by' => $fileValue->created_by,
                                                        'supplier_id' => $fileValue->supplier_id,
                                                        'cost' => $row[$keyAmount],
                                                        'invoice_number' => (!empty($keyInvoiceNumber) && !empty($row[$keyInvoiceNumber])) ? ($row[$keyInvoiceNumber]) : (''),
                                                        'date' =>  (isset($keyInvoiceDate) && !empty($row[$keyInvoiceDate])) ? (($row[$keyInvoiceDate] && $fileValue->supplier_id == 4) ? (Carbon::createFromFormat('Y-m-d H:i:s', $row[$keyInvoiceDate])->format('Y-m-d H:i:s')) : (Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[$keyInvoiceDate]))->format('Y-m-d H:i:s'))) : ($fileValue->start_date),
                                                        'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                        'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                        'customer_number' => $customerNumber[0],
                                                    ]);
                                                } else {  
                                                    $orderLastInsertId = Order::create([
                                                        'attachment_id' => $fileValue->id,
                                                        'created_by' => $fileValue->created_by,
                                                        'supplier_id' => $fileValue->supplier_id,
                                                        'cost' => $row[$keyAmount],
                                                        'invoice_number' => (!empty($keyInvoiceNumber) && !empty($row[$keyInvoiceNumber])) ? ($row[$keyInvoiceNumber]) : (''),
                                                        'date' => (isset($keyInvoiceDate) && !empty($row[$keyInvoiceDate])) ? (Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[$keyInvoiceDate]))->format('Y-m-d H:i:s')) : ($fileValue->start_date),
                                                        'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                        'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                        'customer_number' => ltrim($row[$keyCustomerNumber], '0'),
                                                    ]);
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
                                                    DB::table(
                                                        DB::table('supplier_tables')
                                                        ->select('table_name')
                                                        ->where('supplier_id', $fileValue->supplier_id)
                                                        ->first()
                                                        ->table_name
                                                    )
                                                    ->insert($excelInsertArray);
                                                    DB::table('order_details')->insert($finalInsertArray);
                                                } catch (QueryException $e) {   
                                                    Log::error('Error in YourScheduledTask: ' . $e->getMessage());
                                                    echo "Database insertion failed: " . $e->getMessage();
                                                    echo $e->getTraceAsString();
                                                    die;
                                                }
                                                
                                                unset($finalInsertArray, $excelInsertArray);
                                            }
        
                                            $count++; 
                                        }
                                    } else {
                                        continue;
                                    }
                                }
                            }

                            unset($workSheetArray1, $count);
                            
                            if (isset($finalInsertArray) && !empty($finalInsertArray)) {
                                try {
                                    DB::table('attachments')
                                    ->where('id', $fileValue->id)
                                    ->update([
                                        'cron' => 5
                                    ]);

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

                                    DB::table('order_details')
                                    ->insert($finalInsertArray);
                                } catch (QueryException $e) {   
                                    Log::error('Error in YourScheduledTask: ' . $e->getMessage());
                                    echo "Database insertion failed: " . $e->getMessage();
                                }
                            }
                            unset($finalInsertArray, $finalOrderInsertArray, $excelInsertArray);
                        }
                    try {
                        /** Update the 'cron' field three after processing done */
                        DB::table('attachments')->where('id', $fileValue->id)->update(['cron' => 6]);
    
                        $this->info('Uploaded files processed successfully.');
                    } catch (QueryException $e) {   
                        echo "Database updation failed: " . $e->getMessage();
                        die;
                    }
                } catch (\Exception $e) {
                    /** Update the 'cron' field three after processing done */
                    echo "Error loading spreadsheet: " . $e->getMessage();
                }
            } else {
                echo "No file left to process.";
            }
        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
            echo "Error loading spreadsheet: " . $e->getMessage();
            die;
        } catch (QueryException $e) {   
            echo "Database table attachments select query failed: " . $e->getMessage();
            die;
        }  
    }
}
