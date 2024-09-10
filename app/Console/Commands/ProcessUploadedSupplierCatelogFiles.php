<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\CatalogDetail;
use Illuminate\Support\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessUploadedSupplierCatelogFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-uploaded-supplier-catelog-files';

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
        // ini_set('memory_limit', '1024M');
        // $query = DB::table('orders')
        // ->select('orders.id as id', 'order_product_details.value as cost')
        // ->leftJoin('order_product_details', 'orders.id', '=', 'order_product_details.order_id')
        // ->whereIn('order_product_details.key', ['Total Spend'])
        // ->where('orders.supplier_id', 3)
        // ->get();
        // dd($query);
        // foreach ($query as $key => $value) {
        //     $id = DB::table('orders')
        //     ->where('id', $value->id)
        //     ->update(['cost' => $value->cost]);
        // }

        ini_set('memory_limit', '1024M');
        $query = DB::table('order_details')
        ->select(
            'order_details.order_id as order_id',
            'order_details.invoice_number as invoice_number'
        )
        ->get();
            
        foreach ($query as $value) {
            DB::table('orders')
            ->where('id', $value->order_id)
            ->update(['invoice_number' => $value->invoice_number]);
        }
    }
}
