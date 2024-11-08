<?php

namespace App\Console\Commands;

use Illuminate\Support\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use App\Models\{Order, ManageColumns};

class validateUploadedFile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:validate-uploaded-file';

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
        /** Increasing the memory limit becouse memory limit issue */
        ini_set('memory_limit', '1024M');

        /** This is the folder path where we save the file */
        $destinationPath = public_path('/excel_sheets');

        /** Select those file name where cron is one */
        $fileValue = DB::table('attachments')->select('id', 'supplier_id', 'file_name', 'created_by')->where('cron', '=', 1)->whereNull('deleted_by')->first();
        
        /** Getting suppliers ids and its required columns */
        $suppliers = ManageColumns::getRequiredColumns();

        if ($fileValue !== null && $fileValue->supplier_id != 7) {
            /** Getting the file extension for process file according to the extension */
            $inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($destinationPath . '/' . $fileValue->file_name);

            if ($inputFileType === 'Xlsx') {
                $reader = new Xlsx();
            } elseif ($inputFileType === 'Xls') {
                $reader = new Xls();
            } else {
                Log::error('Unsupported file type: ' . $inputFileType. 'getting this error during file data validating');
            }
            
            /** Loading the file without attached image */
            $spreadSheet = $reader->load($destinationPath . '/' . $fileValue->file_name, 2);

            /** Getting the required date columns from the supplier_fields */
            $columnValues = DB::table('supplier_fields')
            ->select('supplier_id', 'label')
            ->where([
                'deleted' => 0,
                'required_field_id' => 9,
                'supplier_id' => $fileValue->supplier_id,
            ])
            ->get();

            /** Creating the date array using "columnValues" */
            foreach ($columnValues as $key => $value) {
                $columnArray[$value->supplier_id]['invoice_date'] = $value->label;
            }

            /** Checking the all sheets of excel using loop */
            foreach ($spreadSheet->getAllSheets() as $spreadSheets) {
                /** Compairing date of excel sheet data using loop */
                foreach ($spreadSheets->toArray() as $key => $value) {
                    /** Getting sheet columns using array value and array filter */
                    $finalExcelKeyArray1 = array_values(array_filter($value, function ($item) {
                        return !empty($item);
                    }, ARRAY_FILTER_USE_BOTH));
                                
                    /** Clean up the values */
                    $cleanedArray = array_map(function ($values) {
                        /** Remove line breaks and trim whitespace */
                        return str_replace(["\r", "\n"], '', $values);
                    }, $finalExcelKeyArray1);

                    /** If suppliers having required columns */
                    if (isset($suppliers[$fileValue->supplier_id])) {
                        /** Getting the supplier required columns */
                        $supplierValues = $suppliers[$fileValue->supplier_id];
                        if ($fileValue->supplier_id == 4) {
                            /** Check if 'Group ID', 'Payment Method Code' and 'Transaction Source System' exists in the array */
                            $groupIndex = array_search('Group ID', $cleanedArray);
                            $paymentMethodCodeIndex = array_search('Payment Method Code', $cleanedArray);
                            $transactionSourceSystemIndex = array_search('Transaction Source System', $cleanedArray);

                            $groupIndex !== false ? array_splice($cleanedArray, $groupIndex + 1, 0, 'Group ID1') : '';
                            $paymentMethodCodeIndex !== false ? array_splice($cleanedArray, $paymentMethodCodeIndex + 1, 0, 'Payment Method Code1') : '';
                            $transactionSourceSystemIndex !== false ? array_splice($cleanedArray, $transactionSourceSystemIndex + 1, 0, 'Transaction Source System1') : '';                            
                        }

                        /** Checking the difference of supplier excel file columns and database columns */
                        $arrayDiff = array_diff($supplierValues, $cleanedArray);

                        /** Checking the difference if arrayDiff empty then break the loop and go to next step */
                        if (empty($arrayDiff)) {
                            $maxNonEmptyvalue1 = $value;
                            $startIndexValueArray = $key;
                            break;
                        }
                    }
                }

                /** If not able to get the required columns then continue */
                if (!isset($maxNonEmptyvalue1)) {
                    continue;
                }

                /** Remove empty key from the array of excel sheet column name */
                $finalExcelKeyArray1 = array_values(array_filter($maxNonEmptyvalue1, function ($item) {
                    return !empty($item);
                }, ARRAY_FILTER_USE_BOTH));
                            
                /** Clean up the values */
                $cleanedArray = array_map(function ($value) {
                    /** Remove line breaks and trim whitespace */
                    return trim(str_replace(["\r", "\n"], '', $value));
                }, $finalExcelKeyArray1);

                /** In case of grainer supplier we need to skip the first row */
                if ($fileValue->supplier_id == 2) {
                    $startIndex = $startIndexValueArray + 1;
                } else {
                    $startIndex = $startIndexValueArray;
                }

                $chunkSize = 0;
                $dates = [];

                /** Getting date column index */
                if (!empty($columnArray[$fileValue->supplier_id]['invoice_date'])) {
                    dd($columnArray[$fileValue->supplier_id]['invoice_date']);
                    
                    $keyInvoiceDate = array_search($columnArray[$fileValue->supplier_id]['invoice_date'], $cleanedArray);
                }

                if (!empty($keyInvoiceDate)) {
                    foreach ($spreadSheets->toArray() as $key => $row) {
                        print_r($row);
                        if($key > $startIndex){
                            if (!empty($row[$keyInvoiceDate])) {
                                print_r($row);
                                print_r($keyInvoiceDate);
                                dd($row[$keyInvoiceDate]);
                                if ($fileValue->supplier_id == 4) {
                                    $date = explode("-", $row[$keyInvoiceDate]);
                                    if(count($date) <= 2){
                                        $dates[] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[$keyInvoiceDate]))->format('Y-m-d');
                                    } else {
                                        $dates[] = date_format(date_create($row[$keyInvoiceDate]),'Y-m-d');
                                    }
                                } else {
                                    $dates[] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[$keyInvoiceDate]))->format('Y-m-d');
                                }

                                if ($chunkSize == 1000) {
                                    /** Checking date into the orders table */
                                    $fileExist = Order::where(function ($query) use ($dates) {
                                        /** creating date query using loop */
                                        foreach ($dates as $startDate) {
                                            if (!empty($startDate)) {
                                                $query->orWhere('date', '=', $startDate);
                                            }
                                        }
                                    })->where('supplier_id', $fileValue->supplier_id);
                                    
                                    $chunkSize = 0;

                                    if ($fileExist->count() > 0) {
                                        /** Update cron ten means duplicate data into the excel file */
                                        DB::table('attachments')->where('id', $fileValue->id)
                                        ->update([
                                            'cron' => 10
                                        ]);
                                        die;
                                    }
                                }
                            } else {
                                $dates = [];
                            }
            
                            $chunkSize++;
                        }
                    }

                    if (!empty($dates)) {
                        $fileExist = Order::where(function ($query) use ($dates) {
                            foreach ($dates as $startDate) {
                                if (!empty($startDate)) {
                                    $query->orWhere('date', '=', $startDate);
                                }
                            }
                        })->where('supplier_id', $fileValue->supplier_id);
                
                        if ($fileExist->count() > 0) {
                            /** Update cron ten means duplicate data into the excel file */
                            DB::table('attachments')
                            ->where('id', $fileValue->id)
                            ->update([
                                'cron' => 10
                            ]);
                            die;
                        }
                    }
                }
            }
            // dd($fileValue);
            /** Update cron eleven means start processing data into excel */
            DB::table('attachments')
            ->where('id', $fileValue->id)
            ->update(['cron' => 11]);
        } else if ($fileValue->supplier_id == 7) {
            /** Update cron eleven means start processing data into excel */
            DB::table('attachments')
            ->where('id', $fileValue->id)
            ->update(['cron' => 11]);
        } else {

        }
    }
}