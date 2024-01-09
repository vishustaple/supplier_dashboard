<?php

namespace App\Imports;
use App\Models\ExcelData;
use App\Models\UploadedFiles;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

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
    private $destinationPath;

    public function __construct($supplier_id, $fileName, $destinationPath, $cron_check = false)
    {
        $this->fileName = $fileName;
        $this->cron_check = $cron_check;
        $this->supplier_id = $supplier_id;
        $this->destinationPath = $destinationPath;
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
        /** Definig the variable for loop use */
        $start_index_value_array = $maxNonEmptyCount = 0;
        foreach ($collection as $key=>$value) {
            /** Checking not empty columns */
            $nonEmptyCount = $value->filter(function ($item) {
                return !empty($item);
            })->count();
            
            /** if column count is greater then previous row columns count. Then assigen value to '$maxNonEmptyvalue' */
            if ($nonEmptyCount > $maxNonEmptyCount) {
                $maxNonEmptyvalue = $value; 
                $start_index_value_array = $key; 
                $maxNonEmptyCount = $nonEmptyCount;
            }
        }

        $excel_column_name_array = $maxNonEmptyvalue->toArray();
        $maxNonEmptyvalue = null;

        if($this->cron_check == false){
            UploadedFiles::create(['supplier_id' => $this->supplier_id,
            'file_name' => $this->fileName,
            'file_path' => $this->destinationPath,
            'cron' => 1,]); 
        }

        /** Here we slice the collection of excel by using the key last row index of heading of excel collection. 
         * Because we need to select the data column of the excel */
        $collection = $collection->slice($start_index_value_array+2);
        $yourArray = ['Bill Date', 'Shipped Date'];
        foreach ($collection as $key => $row) 
        {
            foreach($row as $key1 => $value){
                if(!empty($value)){
                    // $final_value_array[$value_array_key]['key'] = $excel_column_name_array[$key1];
                    // $final_value_array[$value_array_key]['value'] = $value;
                    // $value_array_key++;
                    
                    ExcelData::create(['supplier_id' => $this->supplier_id,
                    'key' => $excel_column_name_array[$key1],
                    'value' => (in_array($excel_column_name_array[$key1], $yourArray)) ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d') : $value,
                    'file_name' => $this->fileName,]);
                }        
            }
        } 
    }
    
    protected function calculateMaxColumn(array $row)
    {
        // This is a simple example; adjust as needed based on your data
        return count($row);
    }
}
