<?php

namespace App\Console\Commands;

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
        ini_set('memory_limit', '1024M');
        // $query = DB::table('order_details')
        // ->select(
        //     'order_details.order_id as order_id',
        //     'order_details.invoice_number as invoice_number'
        // )
        // ->get();
            
        // foreach ($query as $value) {
        //     DB::table('orders')
        //     ->where('id', $value->order_id)
        //     ->update(['invoice_number' => $value->invoice_number]);
        // }

        // $query = DB::table('supplier_fields')
        // ->select('id', 'raw_label', 'label', 'type')
        // ->whereIn('supplier_id', [1, 2, 3, 4, 5, 6])
        // ->get();
        // // dd($query);
        // foreach ($query as $value) {
        //     $rawLabel = preg_replace('/^_+|_+$/', '', strtolower(preg_replace('/[^A-Za-z0-9_]/', '', str_replace(' ', '_', $value->raw_label))));
        //     DB::table('supplier_fields')
        //     ->where('id', $value->id)
        //     ->update([
        //         'raw_label' => $rawLabel,
        //         'label' => $value->raw_label,
        //         'type' => 'string'
        //     ]);
        // }

        $query = DB::table('orders')
        ->select('id', 'raw_label', 'label', 'type')
        ->whereIn('supplier_id', [1, 2, 3, 4, 5, 6])
        ->get();
        // dd($query);
        foreach ($query as $value) {
            $rawLabel = preg_replace('/^_+|_+$/', '', strtolower(preg_replace('/[^A-Za-z0-9_]/', '', str_replace(' ', '_', $value->raw_label))));
            DB::table('supplier_fields')
            ->where('id', $value->id)
            ->update([
                'raw_label' => $rawLabel,
                'label' => $value->raw_label,
                'type' => 'string'
            ]);
        }
    }
}
