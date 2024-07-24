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
        // /** This is the folder path where we save the file */
        // $destinationPath = public_path('/excel_sheets');

        // $inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($destinationPath . '/test55.xlsx');

        // if ($inputFileType === 'Xlsx') {
        //     $reader = new Xlsx();
        // } elseif ($inputFileType === 'Xls') {
        //     $reader = new Xls();
        // } else {
        //     /** throw new Exception('Unsupported file type: ' . $inputFileType); */
        // }

        // /** Loading excel file using path and name of file from table "uploaded_file" */
        // $spreadSheet = $reader->load($destinationPath . '/test55.xlsx', 2);
        // $spreadSheet = $spreadSheet->getSheet(0)->toArray();

        // foreach ($spreadSheet as $key => $value) {
        //     if ($key == 0) {
        //         continue;
        //     }

        //     DB::table('check_orders')
        //     ->insert([
        //         'master_customer_number' => $value[1],
        //         'master_customer_name' => $value[2],
        //         'bill_to_number' => $value[3],
        //         'bill_to_name' => $value[4],
        //         'ship_to_number' => $value[5],
        //         'order_number' => $value[6],
        //         'ordering_platform' => $value[7],
        //         'fixed_rate_sales_volume' => $value[13],
        //     ]);
        // }

        /** Increasing the memory limit becouse memory limit issue */
        ini_set('memory_limit', '1024M');

        $data = DB::table('staples_order')->select('id', 'order_date_id', 'invoice_date_id')->get();

        foreach ($data as $key => $value) {
            DB::table('staples_order')->where(['id' => $value->id])->update(['order_date_id' => Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->order_date_id))->format('Y-m-d H:i:s'), 'invoice_date_id' => Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->invoice_date_id))->format('Y-m-d H:i:s')]);    
        }
    }
}