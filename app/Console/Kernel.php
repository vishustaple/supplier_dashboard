<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;
use App\Imports\YourImportClass;
use Excel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.  
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        $schedule->call(function () {
            $file_for_process = DB::table('uploaded_files')->select('supllier_id', 'file_name', 'file_path')
            ->where('cron', '=', 1)
            ->get();
            $file_for_process;
        $destinationPath = public_path('/excel_sheets');
        Excel::import(new YourImportClass($supplier_id, $fileName, $destinationPath), $destinationPath . '/' . $fileName);

                  /** Definig the variable for loop use */
        $start_index_value_array = $value_array_key = $maxNonEmptyCount = 0;
        foreach ($collection as $key=>$value) {
            /** Checking not empty columns */
            $nonEmptyCount = $value->filter(function ($item) {
                return !empty($item);
            })->count();
            
            /** if column count is greater then previous row columns count. Then assigen value to '$maxNonEmptyvalue' */
            if ($nonEmptyCount > $maxNonEmptyCount) {
                $maxNonEmptyCount = $nonEmptyCount;
                $maxNonEmptyvalue = $value; 
                $start_index_value_array = $key; 
            }
        }

        $excel_column_name_array = $maxNonEmptyvalue->toArray();

        /** Here we slice the collection of excel by using the key last row index of heading of excel collection. 
         * Because we need to select the data column of the excel */
        $collection = $collection->slice($start_index_value_array+2);
        set_time_limit(200);
        $yourArray = ['Bill Date', 'Shipped Date'];
        foreach ($collection as $key => $row) 
        {
            foreach($row as $key1 => $value){
                if(!empty($value)){
                    // $final_value_array[$value_array_key]['key'] = $excel_column_name_array[$key1];
                    // $final_value_array[$value_array_key]['value'] = $value;
                    // $final_value_array[$value_array_key]['supplier_id'] = 1;
                    // $final_value_array[$value_array_key]['filname'] = 'file_one';
                    // $value_array_key++;
                    
                    // $c = ['supplier_id' => 1,
                    // 'key' => $excel_column_name_array[$key1],
                    // 'value' => (in_array($excel_column_name_array[$key1], $yourArray)) ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d') : $value,
                    // 'file_name' => 'file_one',];
                       
                    //     print_r($c);
                    // }
                    
                    // ExcelData::create(['supplier_id' => 1,
                    // 'key' => $excel_column_name_array[$key1],
                    // 'value' => $value,
                    // 'file_name' => 'file_one',]); 
                }        
            }
        } 
        })->daily();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
