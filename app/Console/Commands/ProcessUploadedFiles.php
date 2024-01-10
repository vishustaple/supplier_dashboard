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
        
        /** Inserting files data into the database after doing excel import */
        foreach ($fileForProcess as $fileKey => $fileValue){
            $reader = new Xlsx(); /** Creating object of php excel library class */ 

            /** Loading excel file using path and name of file from table "uploaded_file" */
            $spreadSheet = $reader->load($destinationPath . '/' . $fileValue->file_name); 
            $sheetCount = $spreadSheet->getSheetCount(); /** Getting sheet count for run loop on index */

            $sheetCount = ($sheetCount > 1) ? $sheetCount - 1 : $sheetCount; /** Handle case if sheet count is one */

            for ($i = 1; $i < $sheetCount; $i++) { 
                $workSheet = $spreadSheet->getSheet($i); /** Getting worksheet using index */
                
                /** Variables to store information about the row with the highest number of columns */
                $workSheetArray = $workSheet->toArray(); 

                $startIndexValueArray = $maxNonEmptyCount = 0;
                
                foreach ($workSheetArray as $key=>$values) {
                    /**Checking not empty columns */
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
                
                $startIndex = $startIndexValueArray + 2; /** Specify the starting index for get the excel column value */

                /** Use array_map to apply array_slice to each sub-array */
                $workSheetArray1 = array_map(function ($subArray) use ($startIndex) {
                    return array_slice($subArray, $startIndex);
                }, $workSheetArray);
                
                /** Remove empty key from the array of excel sheet column name */
                $finalExcelKeyArray = array_values(array_filter($maxNonEmptyValue, function ($item) {
                    return !empty($item);
                }, ARRAY_FILTER_USE_BOTH));

                /** For insert data into the database */
                foreach ($workSheetArray1 as $key => $row) 
                {
                    foreach($row as $key1 => $value){
                        if(!empty($value)){
                            // $final_value_array[$value_array_key]['key'] = $excel_column_name_array[$key1];
                            // $final_value_array[$value_array_key]['value'] = $value;
                            // $value_array_key++;
                            
                            ExcelData::create(['supplier_id' => $fileValue->supplier_id,
                            'key' => $finalExcelKeyArray[$key1],
                            'value' => $value,
                            'file_name' => $fileValue->file_name]);
                        }        
                    }
                } 
            }
        }

        /** Optionally, update the 'cron' field after processing */
        DB::table('uploaded_files')->where('cron', 1)->update(['cron' => 0]);

        $this->info('Uploaded files processed successfully.');
    }
}
