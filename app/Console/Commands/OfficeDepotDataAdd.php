<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use PhpOffice\PhpSpreadsheet\Reader\{Xls, Xlsx};
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use App\Models\{
    Order,
};

class OfficeDepotDataAdd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:office-depot-data-add';

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

        $inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($destinationPath . '/test.xlsx');

        if ($inputFileType === 'Xlsx') {
            $reader = new Xlsx();
        } elseif ($inputFileType === 'Xls') {
            $reader = new Xls();
        } else {
            /** throw new Exception('Unsupported file type: ' . $inputFileType); */
        }

        /** Loading excel file using path and name of file from table "uploaded_file" */
        $spreadSheet = $reader->load($destinationPath . '/test2.xlsx', 2);
        $spreadSheet = $spreadSheet->getSheet(0)->toArray();

        foreach ($spreadSheet as $key => $value) {
            if ($key == 0) {
                continue;
            }

            DB::table('wb_check_data')
            ->insert([
                'account' => $value[0],
                'account_name' => $value[1],
                'item_order' => $value[2],
                'item_name' => $value[3],
                'uom' => $value[4],
                'qty' => $value[5],
                'sum_of_sales' => $value[6],
            ]);
        }
    }
}