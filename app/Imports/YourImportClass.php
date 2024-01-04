<?php

namespace App\Imports;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadingRow;


class YourImportClass implements ToCollection, WithHeadingRow
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)

    { 
          echo "<pre>";
    //     print_r($collection);
         // Get the sheet from the collection
         $sheet = $collection->getDelegate();

         // Determine the maximum column in the Excel file
         $maxColumn = $sheet->getHighestColumn();
 
         // You can now use $maxColumn as needed, for example, to display the maximum column in your application
         dd('Maximum Column:', $maxColumn);
        
        
        $skip = 1;
        $i=510;
        $collection = $collection->slice($skip);
        foreach ($collection as $row) 
        {

            // print_r($row);
            // print_r($row[2]);
            //  echo '</br>';
            // User::create([
            //     'name' => $row[0],
            // ]);
            DB::connection('mysql')->table('users')
            ->insert([ 
                'first_name' => $row[2],
                'last_name' => '20-10-2022',
                'email'=> $i++,
                'password'=>'462543763@!&%^&#',
                'user_type'=>'admin',
            ]);
           
        }  
    }
    protected function calculateMaxColumn(array $row)
    {
        // This is a simple example; adjust as needed based on your data
        return count($row);
    }
}
