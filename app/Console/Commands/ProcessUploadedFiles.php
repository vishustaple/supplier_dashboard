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
            $fileValue = DB::table('uploaded_files')->select('supplier_id', 'file_name', 'start_date', 'end_date', 'created_by')->where('cron', '=', 1)->first();

            // $monthsDifference = $interval->m;
            // $yearsDifference = $interval->y;

            if ($fileValue !== null) {
                /** Add column name here those row you want to skip */
                $skipRowArray = ["Shipto Location Total", "Shipto & Location Total", "TOTAL FOR ALL LOCATIONS", "Total"];
                 
                /** This array for dynmically get column name for save data into tables */
                $columnArray = [ 
                    1 => ['customer_number' => 'SOLD TOACCOUNT','amount' => 'ON-CORESPEND','invoice_no' => '','invoice_date' => ''   
                    ],

                    2 => ['customer_number' => 'Account Number','amount' => 'Actual Price Paid','invoice_no' => 'Invoice Number','invoice_date' => 'Bill Date'],

                    3 => ['customer_number' => 'CUSTOMER ID','amount' => 'Total Spend','invoice_no' => 'Invoice #','invoice_date' => 'Shipped Date'],

                    4 => ['customer_number' => 'MASTER_CUSTOMER','amount' => 'ADJGROSSSALES','invoice_no' => 'INVOICENUMBER','invoice_date' => 'INVOICEDATE'],

                    5 => ['customer_number' => 'Customer Num','amount' => 'Current List','invoice_no' => 'Invoice Num','invoice_date' => 'Invoice Date'],

                    6 => ['customer_number' => 'Leader customer 1','amount' => 'Sales Amount - P','invoice_no' => 'Billing Document','invoice_date' => 'Billing Date'],
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
                            $weeklyCheck = false;
                            $ordersData = DB::table('order_details')->select('order_id')->where('order_file_name', $fileValue->supplier_id."_weekly_".date_format(date_create($fileValue->start_date),"Y/m"))->get();

                            OrderDetails::where('order_file_name', $fileValue->supplier_id."_weekly_".date_format(date_create($fileValue->start_date),"Y/m"))->delete();

                            foreach ($ordersData as $order) {
                                Order::destroy($order->order_id);
                            }
                        }   

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
                            
                            if (($fileValue->supplier_id == 4 && $i == 1) || ($fileValue->supplier_id == 5 && $i == 0) || ($fileValue->supplier_id == 2 && $i == 1)) {
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

                                    if ($fileValue->supplier_id == 3) {
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
                                    
                                    if ($fileValue->supplier_id == 4) {
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

                                    if ($fileValue->supplier_id == 6) {
                                        /** Exploding the "$row" get this  */ 
                                        $c1 = explode(" ", $row[12]);
                                        $c2 = explode(" ", $row[13]);
                                        $c3 = explode(" ", $row[14]);
                                        $c4 = explode(" ", $row[15]);
                                        $c5 = explode(" ", $row[16]);
                                        $c6 = explode(" ", $row[17]);

                                        $customerName = $lc1 = Account::where('customer_number', $c1[0])->first();
                                        $customerName = $lc2 = Account::where('customer_number', $c2[0])->first();
                                        $customerName = $lc3 = Account::where('customer_number', $c3[0])->first();
                                        $customerName = $lc4 = Account::where('customer_number', $c4[0])->first();
                                        $customerName = $lc5 = Account::where('customer_number', $c5[0])->first();
                                        $customerName = $lc6 = Account::where('customer_number', $c6[0])->first();
                                        
                                        if (empty($lc1) && empty($lc2) && empty($lc3) && empty($lc4) && empty($lc5) && empty($lc6)) {
                                            $li1 = Account::create(['customer_number' => $c1[0], 'customer_name' => $c1[1], 'parent_id' => null, 'created_by' => $fileValue->created_by]);
                                            $li2 = Account::create(['customer_number' => $c2[0], 'customer_name' => $c2[1], 'parent_id' => $li1->id, 'created_by' => $fileValue->created_by]);
                                            $li3 = Account::create(['customer_number' => $c3[0], 'customer_name' => $c3[1], 'parent_id' => $li2->id, 'created_by' => $fileValue->created_by]);
                                            $li4 = Account::create(['customer_number' => $c4[0], 'customer_name' => $c4[1], 'parent_id' => $li3->id, 'created_by' => $fileValue->created_by]);
                                            $li5 = Account::create(['customer_number' => $c5[0], 'customer_name' => $c5[1], 'parent_id' => $li4->id, 'created_by' => $fileValue->created_by]);

                                            Account::create(['customer_number' => $c6[0], 'customer_name' => $c6[1], 'parent_id' => $li5->id, 'created_by' => $fileValue->created_by]);

                                        } elseif (!empty($lc1) && empty($lc2) && empty($lc3) && empty($lc4) && empty($lc5) && empty($lc6)) {
                                            $li2 = Account::create(['customer_number' => $c2[0], 'customer_name' => $c2[1], 'parent_id' => $lc1->id, 'created_by' => $fileValue->created_by]);
                                            $li3 = Account::create(['customer_number' => $c3[0], 'customer_name' => $c3[1], 'parent_id' => $li2->id, 'created_by' => $fileValue->created_by]);
                                            $li4 = Account::create(['customer_number' => $c4[0], 'customer_name' => $c4[1], 'parent_id' => $li3->id, 'created_by' => $fileValue->created_by]);
                                            $li5 = Account::create(['customer_number' => $c5[0], 'customer_name' => $c5[1], 'parent_id' => $li4->id, 'created_by' => $fileValue->created_by]);

                                            Account::create(['customer_number' => $c6[0], 'customer_name' => $c6[1], 'parent_id' => $li5->id, 'created_by' => $fileValue->created_by]);

                                        } elseif (!empty($lc1) && !empty($lc2) && empty($lc3) && empty($lc4) && empty($lc5) && empty($lc6)) {
                                            $li3 = Account::create(['customer_number' => $c3[0], 'customer_name' => $c3[1], 'parent_id' => $lc2->id, 'created_by' => $fileValue->created_by]);
                                            $li4 = Account::create(['customer_number' => $c4[0], 'customer_name' => $c4[1], 'parent_id' => $li3->id, 'created_by' => $fileValue->created_by]);
                                            $li5 = Account::create(['customer_number' => $c5[0], 'customer_name' => $c5[1], 'parent_id' => $li4->id, 'created_by' => $fileValue->created_by]);

                                            Account::create(['customer_number' => $c6[0], 'customer_name' => $c6[1], 'parent_id' => $li5->id, 'created_by' => $fileValue->created_by]);

                                        }elseif (!empty($lc1) && !empty($lc2) && !empty($lc3) && empty($lc4) && empty($lc5) && empty($lc6)) {
                                            $li4 = Account::create(['customer_number' => $c4[0], 'customer_name' => $c4[1], 'parent_id' => $lc3->id, 'created_by' => $fileValue->created_by]);
                                            $li5 = Account::create(['customer_number' => $c5[0], 'customer_name' => $c5[1], 'parent_id' => $li4->id, 'created_by' => $fileValue->created_by]);
                                            
                                            Account::create(['customer_number' => $c6[0], 'customer_name' => $c6[1], 'parent_id' => $li5->id, 'created_by' => $fileValue->created_by]);

                                        } elseif (!empty($lc1) && !empty($lc2) && !empty($lc3) && !empty($lc4) && empty($lc5) && empty($lc6)) {
                                            $li5 = Account::create(['customer_number' => $c5[0], 'customer_name' => $c5[1], 'parent_id' => $lc4->id, 'created_by' => $fileValue->created_by]);

                                            Account::create(['customer_number' => $c6[0], 'customer_name' => $c6[1], 'parent_id' => $li5->id, 'created_by' => $fileValue->created_by]);

                                        } elseif (!empty($lc1) && !empty($lc2) && !empty($lc3) && !empty($lc4) && empty($lc5) && empty($lc6)) {
                                            Account::create(['customer_number' => $c6[0], 'customer_name' => $c6[1], 'parent_id' => $lc5->id, 'created_by' => $fileValue->created_by]);

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
                            foreach ($workSheetArray1 as $key => $row) {
                                if (count(array_intersect($skipRowArray, $row)) <= 0) {
                                    foreach ($row as $key1 => $value) {
                                        if(!empty($maxNonEmptyValue[$key1])) {
                                            $finalInsertArray[] = [
                                                'key' => $maxNonEmptyValue[$key1],
                                                'value' => $value,
                                                'file_name' => $fileValue->file_name,
                                                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                            ];  

                                            if (!empty($columnArray[$fileValue->supplier_id]['customer_number']) && $columnArray[$fileValue->supplier_id]['customer_number'] == $maxNonEmptyValue[$key1]) {
                                                $finalOrderInsertArray['customer_number'] = $value;
                                            }

                                            if (!empty($columnArray[$fileValue->supplier_id]['amount']) && $columnArray[$fileValue->supplier_id]['amount'] == $maxNonEmptyValue[$key1]) {
                                                $finalOrderInsertArray['amount'] = $value;
                                            }

                                            if (!empty($columnArray[$fileValue->supplier_id]['invoice_no']) && $columnArray[$fileValue->supplier_id]['invoice_no'] == $maxNonEmptyValue[$key1]) {
                                                if (empty($value)) {
                                                    $finalOrderInsertArray['invoice_no'] = 1;
                                                } else {
                                                    $finalOrderInsertArray['invoice_no'] = $value;
                                                }
                                            } 

                                            if (!empty($columnArray[$fileValue->supplier_id]['invoice_date']) && $columnArray[$fileValue->supplier_id]['invoice_date'] == $maxNonEmptyValue[$key1]) {
                                                if (empty($value)) {
                                                    $finalOrderInsertArray['invoice_date'] = Carbon::now()->format('Y-m-d H:i:s');
                                                } else {
                                                    if ($fileValue->supplier_id == 4) {
                                                        $finalOrderInsertArray['invoice_date'] = Carbon::createFromFormat('Y-m-d H:i:s', $value);
                                                    } else {
                                                        $finalOrderInsertArray['invoice_date'] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value))->format('Y-m-d H:i:s');
                                                    }
                                                }  
                                            }
                                        }
                                    }
                                    
                                    if (isset($finalOrderInsertArray['invoice_no']) && empty($finalOrderInsertArray['invoice_no'])) { 
                                        $systemCreatedInvoice = Order::random_invoice_num();
                                        $orderLastInsertId = Order::create([
                                            'date' => $fileValue->start_date,
                                            'customer_number' => $finalOrderInsertArray['customer_number'],
                                            'created_by' => $fileValue->created_by,
                                            'supplier_id' => $fileValue->supplier_id,
                                            'amount' => $finalOrderInsertArray['amount'],
                                            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                            'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                        ]);
                                    } else {    
                                        $systemCreatedInvoice = $finalOrderInsertArray['invoice_no'];
                                        $orderLastInsertId = Order::create([
                                            'date' => $finalOrderInsertArray['invoice_date'],
                                            'customer_number' => $finalOrderInsertArray['customer_number'],
                                            'created_by' => $fileValue->created_by,
                                            'supplier_id' => $fileValue->supplier_id,
                                            'amount' => $finalOrderInsertArray['amount'],
                                            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                            'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                        ]);
                                    }

                                    if ($weeklyCheck) {
                                        OrderDetails::create([
                                            'order_id' => $orderLastInsertId->id,
                                            'invoice_number' => $systemCreatedInvoice,
                                            'invoice_date' => (isset($finalOrderInsertArray['invoice_date']) && !empty($finalOrderInsertArray['invoice_date'])) ? ($finalOrderInsertArray['invoice_date']) : ($fileValue->start_date),
                                            'order_file_name' => $fileValue->supplier_id."_weekly_".date_format(date_create($fileValue->start_date),"Y/m"),
                                            'created_by' => $fileValue->created_by,
                                            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                            'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                        ]);
                                    } else {
                                       
                                       
                                        OrderDetails::create([
                                            'order_id' => $orderLastInsertId->id,
                                            'invoice_number' => $systemCreatedInvoice,
                                            'invoice_date' => (isset($finalOrderInsertArray['invoice_date']) && !empty($finalOrderInsertArray['invoice_date'])) ? ($finalOrderInsertArray['invoice_date']) : ($fileValue->start_date),
                                            'order_file_name' => $fileValue->supplier_id."_monthly_".date_format(date_create($fileValue->start_date),"Y/m"),
                                            'created_by' => $fileValue->created_by,
                                            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                            'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                        ]);
                                    }
                                    
                                    foreach ($finalInsertArray as &$item) {
                                        if (!isset($item['order_id']) && empty($item['order_id'])) {
                                            $item['order_id'] = $orderLastInsertId->id;
                                        }
                                    }

                                    if ($count == 100) {
                                        $count = 0;
                                        try {
                                            DB::table('order_product_details')->insert($finalInsertArray);
                                        } catch (QueryException $e) {   
                                            echo "Database insertion failed: " . $e->getMessage();
                                        }
                                        
                                        unset($finalInsertArray);
                                    }

                                    $count++; 
                                } else {
                                    continue;
                                }
                            }

                            unset($workSheetArray1, $count, $maxNonEmptyValue);

                            if (!empty($finalInsertArray)) {
                                try {
                                    DB::table('order_product_details')->insert($finalInsertArray);
                                } catch (QueryException $e) {   
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
                    /** Optionally, update the 'cron' field after processing */
                    // DB::table('uploaded_files')->where('cron', 1)->update(['cron' => 0]);

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
