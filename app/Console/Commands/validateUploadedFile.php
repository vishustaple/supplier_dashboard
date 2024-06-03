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
use App\Models\Order;

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
        $fileValue = DB::table('uploaded_files')->select('id', 'supplier_id', 'file_name', 'start_date', 'end_date', 'created_by')->where('cron', '=', 1)->whereNull('deleted_by')->first();
 
        if ($fileValue !== null) {
            $inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($destinationPath . '/' . $fileValue->file_name);

            if ($inputFileType === 'Xlsx') {
                $reader = new Xlsx();
            } elseif ($inputFileType === 'Xls') {
                $reader = new Xls();
            } else {
                // throw new Exception('Unsupported file type: ' . $inputFileType);
            }
            
            $spreadSheet = $reader->load($destinationPath . '/' . $fileValue->file_name, 2);

            $columnValues = DB::table('manage_columns')->select('id', 'supplier_id', 'field_name')->where('supplier_id', $fileValue->supplier_id)->get();

            foreach ($columnValues as $key => $value) {
                if (in_array($value->id, [24, 68, 103, 128, 195, 258])) {
                    $columnArray[$value->supplier_id]['invoice_date'] = $value->field_name;
                }

                if (in_array($value->supplier_id, [7])) {
                    $columnArray[$value->supplier_id]['invoice_date'] = '';
                }
            }
                
            foreach ($spreadSheet->getAllSheets() as $spreadSheets) {
                $maxNonEmptyCount = 0;       
                foreach ($spreadSheets->toArray() as $key=>$value) {
                    /** Checking not empty columns */
                    $nonEmptyCount = count(array_filter(array_values($value), function ($item) {
                        return !empty($item);
                    }));
                    
                    /** If column count is greater then previous row columns count. Then assigen value to '$maxNonEmptyvalue' */
                    if ($nonEmptyCount > $maxNonEmptyCount) {
                        $maxNonEmptyvalues = $maxNonEmptyvalue1 = $value;
                        $startIndexValueArray = $key;
                        $maxNonEmptyCount = $nonEmptyCount;
                    } 
                    
                    /** Stop loop after reading 31 rows from excel file */
                    if($key > 13){
                        break;
                    }
                }

                /** Remove empty key from the array of excel sheet column name */
                $finalExcelKeyArray1 = array_values(array_filter($maxNonEmptyvalue1, function ($item) {
                    return !empty($item);
                }, ARRAY_FILTER_USE_BOTH));
                            
                /** Clean up the values */
                $cleanedArray = array_map(function ($value) {
                    /** Remove line breaks and trim whitespace */
                    return str_replace(["\r", "\n"], '', $value);
                }, $finalExcelKeyArray1);

                if ($fileValue->supplier_id == 7) {
                    foreach ($cleanedArray as $key => $value) {
                        if ($key > 5) {
                            $cleanedArray[$key] = trim("Year_" . substr($cleanedArray[$key], - 2));
                        }
                    }
                }

                if ($fileValue->supplier_id == 2) {
                    $startIndex = $startIndexValueArray + 1;
                } else {
                    $startIndex = $startIndexValueArray;
                }

                $chunkSize = 0; // Adjust as needed
                $dates = [];

                if (!empty($columnArray[$fileValue->supplier_id]['invoice_date'])) {
                    $keyInvoiceDate = array_search($columnArray[$fileValue->supplier_id]['invoice_date'], $cleanedArray);
                }

                if (!empty($keyInvoiceDate)) {
                    foreach ($spreadSheets->toArray() as $key => $row) {
                        if($key > $startIndex){
                            if (!empty($row[$keyInvoiceDate])) {
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
                                    $fileExist = Order::where(function ($query) use ($dates) {
                                        foreach ($dates as $startDate) {
                                            if (!empty($startDate)) {
                                                $query->orWhere('date', '=', $startDate);
                                            }
                                        }
                                    })->where('supplier_id', $fileValue->supplier_id);
                                    
                                    $chunkSize = 0;

                                    if ($fileExist->count() > 0) {
                                        /** Update cron two means start processing data into excel */
                                        DB::table('uploaded_files')->where('id', $fileValue->id)
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
                            /** Update cron two means start processing data into excel */
                            DB::table('uploaded_files')->where('id', $fileValue->id)
                            ->update(['cron' => 10]);
                            die;
                        }
                    }
                }
            }
            /** Update cron two means start processing data into excel */
            DB::table('uploaded_files')->where('id', $fileValue->id)
            ->update(['cron' => 11]);
        }
    }
}