<?php

namespace App\Imports;
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

    public function __construct($suppliername, $fileName, $destinationPath)
    {
        $this->suppliername = $suppliername;
        $this->fileName = $fileName;
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
        UploadedFiles::create(['supplier_id' => $this->suppliername,
        'file_name' => $this->fileName,
        'file_path' => $this->destinationPath,
        'cron' => 1,]); 
    }
    
    protected function calculateMaxColumn(array $row)
    {
        // This is a simple example; adjust as needed based on your data
        return count($row);
    }
}
