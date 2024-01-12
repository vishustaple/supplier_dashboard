<?php

namespace App\Console\Commands;

use App\Models\ExcelData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
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
        try{
            $fileForProcess = DB::table('uploaded_files')->select('supplier_id', 'file_name')->where('cron', '=', 1)->get();
            
            if(!$fileForProcess->isEmpty()){
                $skipRowArray = ["Shipto Location Total", "Shipto & Location Total", "TOTAL FOR ALL LOCATIONS", "Total"];
                try {
                    /** Increasing the memory limit becouse memory limit issue */
                    ini_set('memory_limit', '1024M');

                    /** Inserting files data into the database after doing excel import */
                    foreach ($fileForProcess as $fileKey => $fileValue){    
                        unset($spreadSheet, $reader);
                        
                        $reader = new Xlsx(); /** Creating object of php excel library class */ 

                        /** Loading excel file using path and name of file from table "uploaded_file" */
                        $spreadSheet = $reader->load($destinationPath . '/' . $fileValue->file_name, 2);
                        
                        $sheetCount = $spreadSheet->getSheetCount(); /** Getting sheet count for run loop on index */
                        
                        if(in_array($fileValue->supplier_id, [3, 4])){
                            $sheetCount = ($sheetCount > 1) ? $sheetCount - 2 : $sheetCount; /** Handle case if sheet count is one */
                        }else{
                            $sheetCount = ($sheetCount > 1) ? $sheetCount - 1 : $sheetCount;
                        }
                        
                        // print_r($sheetCount);
                        
                        for ($i = 0; $i < $sheetCount; $i++) {
                            $count = $maxNonEmptyCount = 0;
                            
                            if($fileValue->supplier_id == 5 || $i==1){
                                continue;
                            }

                            $workSheetArray = $spreadSheet->getSheet($i)->toArray(); /** Getting worksheet using index */
                            
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
                                if($key > 20){
                                    break;
                                }
                            }
                            unset($maxNonEmptyCount);

                            // print_r($maxNonEmptyValue);

                            $startIndex = $startIndexValueArray; /** Specify the starting index for get the excel column value */
                            unset($startIndexValueArray);
                            foreach ($workSheetArray as $key => $row) {
                                if($key > $startIndex){
                                    $workSheetArray1[] = $row;
                                }
                            }
                            
                            /** For insert data into the database */
                            foreach ($workSheetArray1 as $key => $row) 
                            {
                                if (count(array_intersect($skipRowArray, $row)) <= 0) {
                                    foreach($row as $key1 => $value){
                                        if(!empty($maxNonEmptyValue[$key1])){
                                            $finalInsertArray[] = ['supplier_id' => $fileValue->supplier_id,
                                            'key' => $maxNonEmptyValue[$key1],
                                            'value' => $value,
                                            'file_name' => $fileValue->file_name];
                                        }
                                    }
    
                                    if($count == 100){
                                        $count = 0;
                                        try{
                                            DB::table('excel_data')->insert($finalInsertArray);
                                        } catch (QueryException $e) {   
                                            echo "Database insertion failed: " . $e->getMessage();
                                        }
                                        
                                        unset($finalInsertArray);
                                    }
                                    $count++; 
                                }else{
                                    continue;
                                }
                            }

                            unset($workSheetArray1, $count, $maxNonEmptyValue);

                            if(!empty($finalInsertArray)){
                                try{
                                    DB::table('excel_data')->insert($finalInsertArray);
                                } catch (QueryException $e) {   
                                    echo "Database insertion failed: " . $e->getMessage();
                                }
                            }

                            unset($finalInsertArray);
                        }
                    }
                } catch (\Exception $e) {
                    echo "Error loading spreadsheet: " . $e->getMessage();
                }

                try{
                    /** Optionally, update the 'cron' field after processing */
                    DB::table('uploaded_files')->where('cron', 1)->update(['cron' => 0]);

                    $this->info('Uploaded files processed successfully.');
                } catch (QueryException $e) {   
                    echo "Database updation failed: " . $e->getMessage();
                }
            }
        } catch (QueryException $e) {   
            echo "Database table uploaded_files select query failed: " . $e->getMessage();
        }  
    }
}
