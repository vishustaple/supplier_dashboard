<?php

namespace App\Imports;
use App\Models\User;
use App\Models\ExcelData;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Concerns\WithEvents;

//use Maatwebsite\Excel\Concerns\WithHeadingRow;
//use Maatwebsite\Excel\Concerns\WithStartRow;
//use Maatwebsite\Excel\Imports\HeadingRowFormatter;


use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Events\BeforeSheet;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Reader;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class YourImportClass implements ToCollection //, WithHeadingRow , WithStartRow

// class YourImportClass implements ToCollection, WithHeadingRow, WithEvents
{
    private $sheetNames = [];
    private $suppliername;
    private $fileName;

    public function __construct($suppliername,$fileName)
    {
        $this->suppliername = $suppliername;
        $this->fileName = $fileName;
        
    }

    public function registerEvents(): array
    {
        return [
            BeforeSheet::class => function (BeforeSheet $event) {
                $this->sheetNames[] = $event->sheet->getTitle();
            

            },
            AfterImport::class => function (AfterImport $event) {
          
            },
        ];
    }



    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)

    { 

        //   echo "<pre>";
        // print_r($collection);
        // die;
    //      // Get the sheet from the collection
    //      $sheet = $collection->getDelegate();

    //      // Determine the maximum column in the Excel file
    //      $maxColumn = $sheet->getHighestColumn();
 
    //      // You can now use $maxColumn as needed, for example, to display the maximum column in your application
    //      dd('Maximum Column:', $maxColumn);
        // $this->startRow();
        
        // $skip = 1;
        // $i=510;
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
        echo "<pre>";        
        // print_r($maxNonEmptyvalue->toArray());
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
        
          
            // User::create([
            //     'name' => $row[0],
            // ]);
            // DB::connection('mysql')->table('users')
            // ->insert([ 
            //     'first_name' => $row[2],
            //     'last_name' => '20-10-2022',
            //     'email'=> $i++,
            //     'password'=>'462543763@!&%^&#',
            //     'user_type'=>'admin',
            // ]);
            
        } 
        // DB::connection('mysql')->table('excel_data')->insert($final_value_array);
        // dd($final_value_array);
        die;

        
        // dd($this->fileName);
        // $sheetNames = $this->sheetNames;
        // dd($sheetNames);
       
        //     // Initialize the maximum column count
        //     $maxColumn = 0;

        //     // Iterate over the rows to find the maximum column count
        //     $collection->each(function ($row) use (&$maxColumn) {
        //         $currentColumnCount = count($row);
        //         $maxColumn = max($maxColumn, $currentColumnCount);
        //     });
    
        //     // Output the highest column value
        //     // dd('Highest Column:', $maxColumn);
        
        
        // // $skip = 1;
        // // $i=510;
        // // $collection = $collection->slice($skip);
        // // foreach ($collection as $row) 
        // // {

        // //     // print_r($row);
        // //     // print_r($row[2]);
        // //     //  echo '</br>';
        // //     // User::create([
        // //     //     'name' => $row[0],
        // //     // ]);
        // //     DB::connection('mysql')->table('users')
        // //     ->insert([ 
        // //         'first_name' => $row[2],
        // //         'last_name' => '20-10-2022',
        // //         'email'=> $i++,
        // //         'password'=>'462543763@!&%^&#',
        // //         'user_type'=>'admin',
        // //     ]);
           
        // // }  
    }
    protected function calculateMaxColumn(array $row)
    {
        // This is a simple example; adjust as needed based on your data
        return count($row);
    }
}
