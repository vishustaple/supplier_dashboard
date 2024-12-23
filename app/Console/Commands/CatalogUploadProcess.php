<?php

namespace App\Console\Commands;

use App\Models\CatalogAttachments;
use App\Models\CatalogSupplierFields;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\{DB, Log};
use PhpOffice\PhpSpreadsheet\Reader\{Xls, Xlsx};

class CatalogUploadProcess extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:catalog-upload-process';

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

        /** Select those file name where cron is 11 */
        $fileValue = CatalogAttachments::select('id', 'supplier_id', 'file_name', 'created_by')
        ->where('cron', '=', 11)
        ->whereNull('deleted_by')
        ->first();
        
        $suppliers = CatalogSupplierFields::getRequiredColumns();

        if ($fileValue !== null && $fileValue->supplier_id == 15) {
            /** Update cron two means start processing data into excel */
            CatalogAttachments::where('id', $fileValue->id)
            ->update(['cron' => 2]);

            /** Add column name here those row you want to skip */
            $skipRowArray = ["Shipto Location Total", "Shipto & Location Total", "TOTAL FOR ALL LOCATIONS", "Total"];
             
            $columnValues = CatalogSupplierFields::select(
                'catalog_supplier_fields.id as id',
                'catalog_supplier_fields.label as label',
                'catalog_supplier_fields.raw_label as raw_label',
                'catalog_required_fields.id as required_field_id',
                'catalog_supplier_fields.supplier_id as supplier_id',
                'catalog_required_fields.field_name as required_field_column',
            )
            ->leftJoin('catalog_required_fields', 'catalog_supplier_fields.required_field_id', '=', 'catalog_required_fields.id')
            ->where(['supplier_id' => $fileValue->supplier_id, 'deleted' => 0])
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

                    CatalogAttachments::where('id', $fileValue->id)
                    ->update(['cron' => 4]);

                    $workSheetArray = $spreadSheet->getSheet($i)->toArray(); /** Getting worksheet using index */

                    foreach ($workSheetArray as $key=>$value) {
                        $finalExcelKeyArray1 = array_values(
                            array_filter(
                                $value,
                                function ($item) {
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
                        $finalExcelKeyArray1 = array_values(
                            array_filter(
                                $value,
                                function ($item) {
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
                                    // if(!empty($maxNonEmptyValue[$key1])) {
                                    //     if (isset($columnArray2[$fileValue->supplier_id][trim($maxNonEmptyValue[$key1])])) {
                                    //         /** Creating the excel insert array for supplier table insert using date column conditions */
                                    //         $excelInsertArray[$key][$columnArray2[$fileValue->supplier_id][trim($maxNonEmptyValue[$key1])]] = $value;
                                    //     }

                                    //     /** We also need to add attachment id, created_at and updated at keys into the excel insert array */
                                    //     $excelInsertArray[$key]['attachment_id'] = $fileValue->id;
                                    //     $excelInsertArray[$key]['created_at'] = Carbon::now()->format('Y-m-d H:i:s');
                                    //     $excelInsertArray[$key]['updated_at'] = Carbon::now()->format('Y-m-d H:i:s');
                                    // }
                                }

                                /** When we create 70 keys array we will insert the array into there spacific table */
                                if ($count == 70) {
                                    $count = 0;
                                    try {
                                       /**Insert data here */
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
                                CatalogAttachments::where('id', $fileValue->id)
                                ->update(['cron' => 5]);

                                /** Inserting the data into the spacific supplier table */
                                
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
                    CatalogAttachments::where('id', $fileValue->id)
                    ->update(['cron' => 6]);

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
        }
    }
}
