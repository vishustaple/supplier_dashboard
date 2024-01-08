<?php

namespace App\Imports;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Events\BeforeSheet;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Reader;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;


class YourImportClass implements ToCollection, WithHeadingRow, WithEvents
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
        
        dd($this->fileName);
        $sheetNames = $this->sheetNames;
        dd($sheetNames);
       
            // Initialize the maximum column count
            $maxColumn = 0;

            // Iterate over the rows to find the maximum column count
            $collection->each(function ($row) use (&$maxColumn) {
                $currentColumnCount = count($row);
                $maxColumn = max($maxColumn, $currentColumnCount);
            });
    
            // Output the highest column value
            // dd('Highest Column:', $maxColumn);
        
        
        // $skip = 1;
        // $i=510;
        // $collection = $collection->slice($skip);
        // foreach ($collection as $row) 
        // {

        //     // print_r($row);
        //     // print_r($row[2]);
        //     //  echo '</br>';
        //     // User::create([
        //     //     'name' => $row[0],
        //     // ]);
        //     DB::connection('mysql')->table('users')
        //     ->insert([ 
        //         'first_name' => $row[2],
        //         'last_name' => '20-10-2022',
        //         'email'=> $i++,
        //         'password'=>'462543763@!&%^&#',
        //         'user_type'=>'admin',
        //     ]);
           
        // }  
    }
    protected function calculateMaxColumn(array $row)
    {
        // This is a simple example; adjust as needed based on your data
        return count($row);
    }
}
