<?php

namespace App\Console\Commands;

use App\Models\ExcelData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

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

        /** Select those file name where cron is one */
        $fileForProcess = DB::table('uploaded_files')->select('supplier_id', 'file_name', 'file_path')->where('cron', '=', 1)->get();

        $startIndexValueArray = $count = $maxNonEmptyCount = 0;
        
        try {
            /** Inserting files data into the database after doing excel import */
            foreach ($fileForProcess as $fileKey => $fileValue){
                $reader = new Xlsx(); /** Creating object of php excel library class */ 

                /** Loading excel file using path and name of file from table "uploaded_file" */
                $spreadSheet = $reader->load($destinationPath . '/' . $fileValue->file_name);
               
                $sheetCount = $spreadSheet->getSheetCount(); /** Getting sheet count for run loop on index */

                $sheetCount = ($sheetCount > 1) ? $sheetCount - 1 : $sheetCount; /** Handle case if sheet count is one */
                print_r($sheetCount);

                for ($i = 1; $i <= $sheetCount; $i++) {
                    $finalInsertArray = $workSheetArray1 = $finalInsertArray = $maxNonEmptyValue = []; 
                    $workSheet = $spreadSheet->getSheet($i); /** Getting worksheet using index */
                    
                    /** Variables to store information about the row with the highest number of columns */
                    $workSheetArray = $workSheet->toArray(); 
                    $workSheet = null;
                    foreach ($workSheetArray as $key=>$values) {
                        /** Checking not empty columns */
                        $nonEmptyCount = count(array_filter(array_values($values), function ($item) {
                            return !empty($item);
                        }));
                        
                        /** if column count is greater then previous row columns count. Then assigen value to '$maxNonEmptyvalue' */
                        if ($nonEmptyCount > $maxNonEmptyCount) {
                            $maxNonEmptyValue = $values;
                            $startIndexValueArray = $key;
                            $maxNonEmptyCount = $nonEmptyCount;
                        } 
                        
                        /** Stop loop after reading 31 rows from excel file */
                        if($key > 30){
                            break;
                        }
                    }
                    print_r($i);
                    // print_r($maxNonEmptyValue);

                    /** In case of GRAINGER */
                    if($fileValue->supplier_id == 1){
                        $startIndex = $startIndexValueArray; /** Specify the starting index for get the excel column value */
                    }
                    
                    foreach ($workSheetArray as $key => $row) {
                        if($key > $startIndex){
                            $workSheetArray1[] = $row;
                        }
                    }
                    $workSheetArray = [];
                    // print_r($workSheetArray1);
                    // die;
                    /** For insert data into the database */
                    foreach ($workSheetArray1 as $key => $row) 
                    {
                        foreach($row as $key1 => $value){
                            if(!empty($maxNonEmptyValue[$key1])){
                                // $final_value_array[$value_array_key]['key'] = $excel_column_name_array[$key1];
                                // $final_value_array[$value_array_key]['value'] = $value;
                                // $value_array_key++;

                                $finalInsertArray[] = ['supplier_id' => $fileValue->supplier_id,
                                'key' => $maxNonEmptyValue[$key1],
                                'value' => $value,
                                'file_name' => $fileValue->file_name];

                                // ExcelData::create(['supplier_id' => $fileValue->supplier_id,
                                // 'key' => $finalExcelKeyArray[$key1],
                                // 'value' => $value,
                                // 'file_name' => $fileValue->file_name]);
                            }    
                        }

                        if($count == 100){
                            $count = 0;
                            DB::table('excel_data')->insert($finalInsertArray);
                            $finalInsertArray = [];
                        }
                        $count++; 
                    }

                    if(!empty($finalInsertArray)){
                        DB::table('excel_data')->insert($finalInsertArray);
                    } 
                }
            }
        } catch (\Exception $e) {
            echo "Error loading spreadsheet: " . $e->getMessage();
        }

        /** Optionally, update the 'cron' field after processing */
        DB::table('uploaded_files')->where('cron', 1)->update(['cron' => 0]);

        $this->info('Uploaded files processed successfully.');
    }
}
