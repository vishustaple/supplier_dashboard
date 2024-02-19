<?php

namespace App\Console\Commands;

use Illuminate\Support\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use App\Models\{Account, Order, OrderDetails, UploadedFiles, RecordType};

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
        // $customerNumber = DB::connection('mysql')->table('accounts')->select('customer_number')->get();
        // if ($customerNumber->count() > 0) {
        //     foreach ($customerNumber as $key => $value) {
        //         $recordMatch = DB::connection('mysql2')->table('account master')->git('Account Number', $value->customer_number)->first();
        //         print_r($recordMatch);
        //         if (!empty($recordMatch)) {
        //             print_r($recordMatch);
        //             die;
        //             $recordMatch = $recordMatch->toArray();
        //             DB::connection('mysql')->table('accounts')->where('customer_number', $value->customer_number)
        //             ->update([
        //                 'internal_reporting_name' => $recordMatch['InternalReportingName'],
        //                 'qbr' => $recordMatch['QBR'],
        //                 'sf_cat' => $recordMatch['SF Cat'],
        //                 'comm_rate' => $recordMatch['Comm Rate'],
        //                 'spend_name' => $recordMatch['Spend Name'],
        //                 'record_type' => $recordMatch['Record Type'],
        //                 'rebate_freq' => $recordMatch['Rebate Freq'],
        //                 'member_rebate' => $recordMatch['Member Rebate'],
        //                 'management_fee' => $recordMatch['Management Fee'],
        //                 'supplier_acct_rep' => $recordMatch['Supplier Acct Rep'],
        //                 'category_supplier' => $recordMatch['Category Supplier'],
        //                 'cpg_sales_representative' => $recordMatch['CPG Sales Representative'],
        //                 'cpg_customer_service_rep' => $recordMatch['CPG Customer Service Rep'],
        //             ]);
        //         }
        //         unset($recordMatch);
        //     }
        // }

        
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
                 
                /** This array for dynmically get column name for save data into tables */
                $columnArray = [ 
                    1 => ['customer_name' => 'SOLD TO NAME','customer_number' => 'SOLD TOACCOUNT', 'amount' => 'ON-CORESPEND', 'invoice_no' => '', 'invoice_date' => ''],

                    2 => ['gd_customer_number' => 'Track Code', 'gd_customer_name' => 'Track Code Name', 'p_customer_number' => 'Sub track Code', 'p_customer_name' => 'Sub Track Code Name', 'customer_name' => 'Account Name', 'customer_number' => 'Account Number', 'amount' => 'Actual Price Paid', 'invoice_no' => 'Invoice Number', 'invoice_date' => 'Bill Date'],

                    3 => ['gd_customer_number' => 'CUSTOMER GRANDPARENT ID', 'gd_customer_name' => 'CUSTOMER GRANDPARENT NM', 'p_customer_number' => 'CUSTOMER PARENT ID', 'p_customer_name' => 'CUSTOMER PARENT NM', 'customer_number' => 'CUSTOMER ID', 'customer_name' => 'CUSTOMER NM', 'amount' => 'Total Spend', 'invoice_no' => 'Invoice #', 'invoice_date' => 'Shipped Date'],

                    4 => ['customer_number' => 'MASTER_CUSTOMER', 'customer_name' => 'MASTER_NAME', 'amount' => 'ADJGROSSSALES', 'invoice_no' => 'INVOICENUMBER', 'invoice_date' => 'INVOICEDATE'],

                    5 => ['customer_name' => 'Customer Name', 'customer_number' => 'Customer Num', 'amount' => 'Current List', 'invoice_no' => 'Invoice Num', 'invoice_date' => 'Invoice Date'],

                    6 => ['customer_number2' => 'Leader customer 2', 'customer_number3' => 'Leader customer 3', 'customer_number4' => 'Leader customer 4', 'customer_number5' => 'Leader customer 5', 'customer_number6' => 'Leader customer 6', 'customer_number' => 'Leader customer 1', 'amount' => 'Sales Amount - P', 'invoice_no' => 'Billing Document', 'invoice_date' => 'Billing Date'],

                    7 => ['customer_number' => 'Account ID', 'amount' => '', 'invoice_no' => '', 'invoice_date' => ''],

                    8 => ['gd_customer_number' => 'CUSTOMER GRANDPARENT ID', 'gd_customer_name' => 'CUSTOMER GRANDPARENT NM', 'p_customer_number' => 'CUSTOMER PARENT ID', 'p_customer_name' => 'CUSTOMER PARENT NM', 'customer_number' => 'CUSTOMER ID', 'amount' => 'Total Spend', 'invoice_no' => 'Invoice #', 'invoice_date' => 'Shipped Date'],
                ];

                try {
                    /** Increasing the memory limit becouse memory limit issue */
                    ini_set('memory_limit', '1024M');

                    /** Inserting files data into the database after doing excel import */
                    // foreach ($fileValue as $fileKey => $fileValue) {
                        // dd($fileValue);
                        $date1 = Carbon::parse($fileValue->start_date);
                        $date2 = Carbon::parse($fileValue->end_date);

                        /** Calculate the difference between the two dates */
                        $interval = $date1->diff($date2);

                        /** Access the difference in days, months, and years */
                        $daysDifference = $interval->days;

                        if ($daysDifference <= 7) {
                            $weeklyCheck = true;
                        } else {
                            // $weeklyCheck = false;
                            // $ordersData = DB::table('order_details')->select('order_id')->where('order_file_name', $fileValue->supplier_id."_weekly_".date_format(date_create($fileValue->start_date),"Y/m"))->get();

                            // OrderDetails::where('order_file_name', $fileValue->supplier_id."_weekly_".date_format(date_create($fileValue->start_date),"Y/m"))->delete();

                            // foreach ($ordersData as $order) {
                            //     Order::destroy($order->order_id);
                            // }
                        }   

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

                        for ($i = 0; $i <= $sheetCount; $i++) {
                            $count = $maxNonEmptyCount = 0;

                            // print_r($i);
                            
                            if (($sheetCount == 1 && $i == 1 && $fileValue->supplier_id != 5) || ($fileValue->supplier_id == 5 && $i == 0) || ($fileValue->supplier_id == 7 && in_array($i, [0, 1, 3, 4, 5, 6, 7]))) {
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
                                    if ($key >= 16) {
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
                                    
                                    // if ($fileValue->supplier_id == 4) {
                                    //     $gdPerent = Account::where('customer_number', $row[$keyParent])->first();
                                    //     $perent = Account::where('customer_number', $row[$keyCustomer])->first();

                                    //     if (empty($gdPerent) && empty($perent)) {
                                    //         $lastInsertGdPerentId = Account::create(['customer_number' => $row[$keyParent], 'alies' => $row[$keyParentName], 'parent_id' => null, 'created_by' => $fileValue->created_by]);

                                    //         Account::create(['customer_number' => $row[$keyCustomer], 'alies' => $row[$keyCustomerName], 'parent_id' => $lastInsertGdPerentId->id, 'created_by' => $fileValue->created_by]);

                                    //     } elseif (!empty($gdPerent) && empty($perent)) {
                                    //         Account::create(['customer_number' => $row[$keyCustomer], 'alies' => $row[$keyCustomerName], 'parent_id' => $gdPerent->id, 'created_by' => $fileValue->created_by]);

                                    //     } else {
                                    //         // echo "hello";
                                    //     }
                                    // }

                                    if ($fileValue->supplier_id == 6) {
                                        if (!empty($columnArray[$fileValue->supplier_id]['customer_number2'])) {
                                            $keyCustomer2 = array_search($columnArray[$fileValue->supplier_id]['customer_number2'], $maxNonEmptyValue);
                                        }

                                        if (!empty($columnArray[$fileValue->supplier_id]['customer_number3'])) {
                                            $keyCustomer3 = array_search($columnArray[$fileValue->supplier_id]['customer_number3'], $maxNonEmptyValue);
                                        }

                                        if (!empty($columnArray[$fileValue->supplier_id]['customer_number4'])) {
                                            $keyCustomer4 = array_search($columnArray[$fileValue->supplier_id]['customer_number4'], $maxNonEmptyValue);
                                        }

                                        if (!empty($columnArray[$fileValue->supplier_id]['customer_number5'])) {
                                            $keyCustomer5 = array_search($columnArray[$fileValue->supplier_id]['customer_number5'], $maxNonEmptyValue);
                                        }

                                        if (!empty($columnArray[$fileValue->supplier_id]['customer_number6'])) {
                                            $keyCustomer6 = array_search($columnArray[$fileValue->supplier_id]['customer_number6'], $maxNonEmptyValue);
                                        }

                                        /** Exploding the "$row" get this  */ 
                                        $customerName1 = $c1 = explode(" ", $row[$keyCustomer]);
                                        $customerName2 = $c2 = explode(" ", $row[$keyCustomer2]);
                                        $customerName3 = $c3 = explode(" ", $row[$keyCustomer3]);
                                        $customerName4 = $c4 = explode(" ", $row[$keyCustomer4]);
                                        $customerName5 = $c5 = explode(" ", $row[$keyCustomer5]);
                                        $customerName6 = $c6 = explode(" ", $row[$keyCustomer6]);

                                        $lc1 = Account::where('customer_number', $c1[0])->first();
                                        $lc2 = Account::where('customer_number', $c2[0])->first();
                                        $lc3 = Account::where('customer_number', $c3[0])->first();
                                        $lc4 = Account::where('customer_number', $c4[0])->first();
                                        $lc5 = Account::where('customer_number', $c5[0])->first();
                                        $lc6 = Account::where('customer_number', $c6[0])->first();

                                        /** Here we unsetting the zero index because we need full name of customer */
                                        unset($customerName1[0], $customerName2[0], $customerName3[0], $customerName4[0], $customerName5[0], $customerName6[0]);
 
                                        $customerName1 = implode(" ", $customerName1);
                                        $customerName2 = implode(" ", $customerName2);
                                        $customerName3 = implode(" ", $customerName3);
                                        $customerName4 = implode(" ", $customerName4);
                                        $customerName5 = implode(" ", $customerName5);
                                        $customerName6 = implode(" ", $customerName6);
                                        
                                        if (empty($lc1) && empty($lc2) && empty($lc3) && empty($lc4) && empty($lc5) && empty($lc6)) {
                                            $li1 = Account::create(['category_supplier' => $fileValue->supplier_id, 'customer_number' => $c1[0], 'alies' => $customerName1, 'parent_id' => null, 'created_by' => $fileValue->created_by]);
                                            $li2 = Account::create(['category_supplier' => $fileValue->supplier_id, 'customer_number' => $c2[0], 'alies' => $customerName2, 'parent_id' => $li1->id, 'created_by' => $fileValue->created_by]);
                                            $li3 = Account::create(['category_supplier' => $fileValue->supplier_id, 'customer_number' => $c3[0], 'alies' => $customerName3, 'parent_id' => $li2->id, 'created_by' => $fileValue->created_by]);
                                            $li4 = Account::create(['category_supplier' => $fileValue->supplier_id, 'customer_number' => $c4[0], 'alies' => $customerName4, 'parent_id' => $li3->id, 'created_by' => $fileValue->created_by]);
                                            $li5 = Account::create(['category_supplier' => $fileValue->supplier_id, 'customer_number' => $c5[0], 'alies' => $customerName5, 'parent_id' => $li4->id, 'created_by' => $fileValue->created_by]);

                                            Account::create(['category_supplier' => $fileValue->supplier_id, 'customer_number' => $c6[0], 'alies' => $customerName6, 'parent_id' => $li5->id, 'created_by' => $fileValue->created_by]);

                                        } elseif (!empty($lc1) && empty($lc2) && empty($lc3) && empty($lc4) && empty($lc5) && empty($lc6)) {
                                            $li2 = Account::create(['category_supplier' => $fileValue->supplier_id, 'customer_number' => $c2[0], 'alies' => $customerName2, 'parent_id' => $lc1->id, 'created_by' => $fileValue->created_by]);
                                            $li3 = Account::create(['category_supplier' => $fileValue->supplier_id, 'customer_number' => $c3[0], 'alies' => $customerName3, 'parent_id' => $li2->id, 'created_by' => $fileValue->created_by]);
                                            $li4 = Account::create(['category_supplier' => $fileValue->supplier_id, 'customer_number' => $c4[0], 'alies' => $customerName4, 'parent_id' => $li3->id, 'created_by' => $fileValue->created_by]);
                                            $li5 = Account::create(['category_supplier' => $fileValue->supplier_id, 'customer_number' => $c5[0], 'alies' => $customerName5, 'parent_id' => $li4->id, 'created_by' => $fileValue->created_by]);

                                            Account::create(['category_supplier' => $fileValue->supplier_id, 'customer_number' => $c6[0], 'alies' => $customerName6, 'parent_id' => $li5->id, 'created_by' => $fileValue->created_by]);

                                        } elseif (!empty($lc1) && !empty($lc2) && empty($lc3) && empty($lc4) && empty($lc5) && empty($lc6)) {
                                            $li3 = Account::create(['category_supplier' => $fileValue->supplier_id, 'customer_number' => $c3[0], 'alies' => $customerName3, 'parent_id' => $lc2->id, 'created_by' => $fileValue->created_by]);
                                            $li4 = Account::create(['category_supplier' => $fileValue->supplier_id, 'customer_number' => $c4[0], 'alies' => $customerName4, 'parent_id' => $li3->id, 'created_by' => $fileValue->created_by]);
                                            $li5 = Account::create(['category_supplier' => $fileValue->supplier_id, 'customer_number' => $c5[0], 'alies' => $customerName5, 'parent_id' => $li4->id, 'created_by' => $fileValue->created_by]);

                                            Account::create(['category_supplier' => $fileValue->supplier_id, 'customer_number' => $c6[0], 'alies' => $customerName6, 'parent_id' => $li5->id, 'created_by' => $fileValue->created_by]);

                                        }elseif (!empty($lc1) && !empty($lc2) && !empty($lc3) && empty($lc4) && empty($lc5) && empty($lc6)) {
                                            $li4 = Account::create(['category_supplier' => $fileValue->supplier_id, 'customer_number' => $c4[0], 'alies' => $customerName4, 'parent_id' => $lc3->id, 'created_by' => $fileValue->created_by]);
                                            $li5 = Account::create(['category_supplier' => $fileValue->supplier_id, 'customer_number' => $c5[0], 'alies' => $customerName5, 'parent_id' => $li4->id, 'created_by' => $fileValue->created_by]);
                                            
                                            Account::create(['category_supplier' => $fileValue->supplier_id, 'customer_number' => $c6[0], 'alies' => $customerName6, 'parent_id' => $li5->id, 'created_by' => $fileValue->created_by]);

                                        } elseif (!empty($lc1) && !empty($lc2) && !empty($lc3) && !empty($lc4) && empty($lc5) && empty($lc6)) {
                                            $li5 = Account::create(['category_supplier' => $fileValue->supplier_id, 'customer_number' => $c5[0], 'alies' => $customerName5, 'parent_id' => $lc4->id, 'created_by' => $fileValue->created_by]);

                                            Account::create(['category_supplier' => $fileValue->supplier_id, 'customer_number' => $c6[0], 'alies' => $customerName6, 'parent_id' => $li5->id, 'created_by' => $fileValue->created_by]);

                                        } elseif (!empty($lc1) && !empty($lc2) && !empty($lc3) && !empty($lc4) && empty($lc5) && empty($lc6)) {
                                            Account::create(['category_supplier' => $fileValue->supplier_id, 'customer_number' => $c6[0], 'alies' => $customerName6, 'parent_id' => $lc5->id, 'created_by' => $fileValue->created_by]);

                                        } else {
                                            // echo "hello";
                                        }
                                    }

                                    if (in_array($fileValue->supplier_id, [1, 4, 5])) {
                                        $customer = Account::where('customer_number', $row[$keyCustomer])->first();
                                        if (empty($customer)) {
                                            Account::create(['category_supplier' => $fileValue->supplier_id, 'customer_number' => $row[$keyCustomer], 'alies' => $row[$keyCustomerName], 'parent_id' => null, 'created_by' => $fileValue->created_by]);
                                        }
                                    }
                                }
                            }

                            /** For insert data into the database */
                            foreach ($workSheetArray1 as $key => $row) {
                                if (count(array_intersect($skipRowArray, $row)) <= 0) {
                                    if (!empty($columnArray[$fileValue->supplier_id]['customer_number'])) {
                                        $keyCustomerNumber = array_search($columnArray[$fileValue->supplier_id]['customer_number'], $maxNonEmptyValue);
                                    }

                                    if (!empty($columnArray[$fileValue->supplier_id]['amount'])) {
                                        if ($fileValue->supplier_id == 1) {
                                            $keyOffCoreAmount = array_search('OFF-CORESPEND', $maxNonEmptyValue);
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
                                                    // 'invoice_number' => (isset($keyInvoiceNumber) && !empty($row[$keyInvoiceNumber])) ? ($row[$keyInvoiceNumber]) : (OrderDetails::randomInvoiceNum((isset($orderDetailsArray) ? ($orderDetailsArray) : ([])))),
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
                                    // }

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
                    // }
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
