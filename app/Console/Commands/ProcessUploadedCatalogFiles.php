<?php

namespace App\Console\Commands;

use App\Models\Catalog;
use Illuminate\Support\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
class ProcessUploadedCatalogFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-uploaded-catalog-files';

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
        // ini_set('memory_limit', '1024M');
        
        /** This is the folder path where we save the file */
        $destinationPath = public_path('/excel_sheets');
        ini_set('memory_limit', '19024M');
        // print_r($fileValue->created_by);die;
        $reader = new Xlsx(); /** Creating object of php excel library class */

        $supplierId = 3;
        $fileNameArray = [
            // 3 => ['odCatelog1.xlsx', 'odCatelog2.xlsx'],
            // 3 => ['odCatelog4.xlsx', 'odCatelog3.xlsx'],
            // 3 => ['odCatelog6.xlsx'],
            // 3 => ['odCatelog8.xlsx'],
            // 3 => ['odCatelog10.xlsx'], 
            // 3 => ['odCatelog12.xlsx'],
            3 => ['odCatelog13.xlsx'],
            // 3 => ['odCatelog14.xlsx'],
            // 3 => ['odCatelog15.xlsx', 'odCatelog16.xlsx'], 
            // 3 => ['odCatelog17.xlsx', 'odCatelog18.xlsx'],
            // 3 => ['odCatelog19.xlsx', 'odCatelog20.xlsx'], 
            // 3 => ['odCatelog21.xlsx', 'odCatelog22.xlsx'], 
            // 3 => ['odCatelog23.xlsx', 'odCatelogk24.xlsx'],
            5 => ['CatalogWBM.xlsx'], 
            4 => ['CatelogStaples.xlsx']
        ];
        for ($i=0; $i <= count($fileNameArray[3]); $i++) { 
            /** Loading excel file using path and name of file from table "uploaded_file" */
            $spreadSheet = $reader->load($destinationPath . '/' . $fileNameArray[3][$i], 2);    
          
            
            $sheetCount = $spreadSheet->getSheetCount(); /** Getting sheet count for run loop on index */
            unset($workSheetArray, $maxNonEmptyValue);
            for ($i=0; $i < $sheetCount; $i++) { 
                $workSheetArray = $spreadSheet->getSheet(0)->toArray();
                $count = $maxNonEmptyCount = 0;
                
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
                    if ($key > 2) {
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
    
                foreach ($workSheetArray as $key => $row) {
                    if ($key == 0) {
                        continue;
                    }
    
                    if ($supplierId == 3) {
                        $catalogLastInsertId = Catalog::create([
                            'sku' => (isset($row[0]) && !empty($row[0])) ? ($row[0]) : ('0'),
                            'price' => (isset($row[4]) && !empty($row[4])) ? ($row[4]) : ('0'),
                            'created_by' => 1,
                            'supplier_id' => $supplierId,
                            'description' => (isset($row[8]) && !empty($row[8])) ? ($row[8]) : ('0')
                        ]);
                    } elseif ($supplierId == 4) {
                        $catalogLastInsertId = Catalog::create([
                            'sku' => (isset($row[0]) && !empty($row[0])) ? ($row[0]) : ('0'),
                            'price' => (isset($row[5]) && !empty($row[5])) ? ($row[5]) : ('0'),
                            'created_by' => 1,
                            'supplier_id' => $supplierId,
                            'description' => (isset($row[1]) && !empty($row[1])) ? ($row[1]) : ('0')
                        ]);
                    } elseif ($supplierId == 5) {
                        $catalogLastInsertId = Catalog::create([
                            'sku' => (isset($row[0]) && !empty($row[0])) ? ($row[0]) : ('0'),
                            'price' => (isset($row[5]) && !empty($row[5])) ? ($row[5]) : ('0'),
                            'created_by' => 1,
                            'supplier_id' => $supplierId,
                            'description' => (isset($row[3]) && !empty($row[3])) ? ($row[3]) : ('0')
                        ]);
                    } else {
    
                    }
                    
                    foreach ($row as $key1 => $value) {
                        if(!empty($maxNonEmptyValue[$key1])) {     
                            $finalInsertArray[] = [
                                'table_value' => $value,
                                'created_by' => 1,
                                'table_key' => $maxNonEmptyValue[$key1],
                                'catalog_id' => $catalogLastInsertId->id,
                                'file_name' => 'Account Info.xlsx',
                                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                            ];  
                        }
                    }
    
                    if ($count == 100) {
                        $count = 0;
                        try {
                            DB::table('catalog_details')->insert($finalInsertArray);
                        } catch (QueryException $e) {   
                            Log::error('Error in YourScheduledTask: ' . $e->getMessage());
                            echo "Database insertion failed: " . $e->getMessage();
                            die;
                        }
                        
                        unset($finalInsertArray);
                    }
                    $count++; 
                }
                if (!empty($finalInsertArray)) {
                    try {
                        DB::table('catalog_details')->insert($finalInsertArray);
                    } catch (QueryException $e) {   
                        Log::error('Error in YourScheduledTask: ' . $e->getMessage());
                        echo "Database insertion failed: " . $e->getMessage();
                    }
                }
    
                unset($finalInsertArray);
    
            }
        }

        ini_set('memory_limit', '1024M');
    }
}
