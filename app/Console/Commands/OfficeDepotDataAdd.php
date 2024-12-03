<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\{DB, File, Log};
use Illuminate\Database\QueryException;
use PhpOffice\PhpSpreadsheet\Reader\{Xls, Xlsx};
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use App\Models\{
    Account,
    ManageColumns,
    Order,
    UploadedFiles,
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
    public function handle() {
        $totalValue = DB::table('orders')
        ->join('order_details', 'orders.id', '=', 'order_details.order_id')
        ->where('orders.customer_number', 'LIKE', '46542083')
        ->where('order_details.supplier_field_id', 65)
        ->where('orders.supplier_id', 3)
        ->whereBetween('orders.date', ['2024-06-01', '2024-11-30'])
        ->sum('order_details.value');
        
        dd($totalValue);
        
        $supplierField = DB::table('master_account_detail')
        ->select('id', 'account_name')
        // //grainer
        // // ->where('id', '>=', 449)
        // // ->where('id', '<=', 478)

        // //staple
        // // ->where('id', '>=', 71)
        // // ->where('id', '<=', 104)

        // //odp weekly
        // // ->where('id', '>=', 571)
        // // ->where('id', '<=', 629)

        // ->where('supplier_id', 3)
        ->get();
        // dd($supplierField);
        foreach ($supplierField as $key => $value) {
            // ->where('order_details.id', '<=', 39127519)
            // ->whereIn('order_details.key', ['Payment Method Code1', 'Transaction Source System1'])
            // DB::table('order_details')
            // ->join('orders', 'orders.id', '=', 'order_details.order_id')
            // ->where('orders.supplier_id', '=', 3) // Add '=' for clarity
            // ->where('order_details.key', '=', $value->label) // Add '=' for clarity
            // ->where('orders.date', '!=', '0000-00-00 00:00:00')
            // // ->whereNull('order_details.supplier_field_id')
            DB::table('master_account_detail')
            ->where('id', $value->id)
            ->update(['account_name' => trim($value->account_name)]);
            // ->delete();

            // ->orderBy('key', 'desc')
            // ->limit(10)
            // ->get();
            // dd($supplierFieldId);
        }

        // DB::table('order_details')
        // ->join('orders', 'orders.id', '=', 'order_details.order_id')
        // ->whereIn('order_details.key', ['Payment Method Code1', 'Transaction Source System1'])
        // ->where('orders.supplier_id', '=', 14)
        // ->whereNull('order_details.supplier_field_id')
        // ->delete();
    }
}