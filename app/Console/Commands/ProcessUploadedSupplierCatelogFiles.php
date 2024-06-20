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
        // $supplierId = [3, 4, 5];
        // $tableName = [3 => 'catalog_od', 4 =>'catalog_staples', 5 =>'catalog_wbm']; 
        // ini_set('memory_limit', '1024M');

        // $supplierId = [3];
        // $tableName = [3 => 'catalog_od']; 

        // $supplierId = [4, 5];
        // $tableName = [4 =>'catalog_staples', 5 =>'catalog_wbm']; 

        // $catelogTableKeyArray = [
        //     3 =>[
        //         'UM' => 'um',
        //         'WBE' => 'wbe',
        //         'MBE' => 'mbe',
        //         'Sugg' => 'sugg',
        //         'Item' => 'item', 
        //         'Recycled' => 'recycled',
        //         'Sku Number' => 'sku_number',
        //         'Vendor Prd#' => 'vendor_prd',
        //         'Vendor Name' => 'vendor_name',
        //         'Platinum Price' => 'platinum_price',
        //         'Preferred Price' => 'preferred_price',
        //         'Dept Description' => 'dept_description',
        //         'Class Description' => 'class_description',
        //         'Preferred Price Method' => 'preferred_price_method',
        //         'Platinum Price Method' => 'platinum_price_method',
        //     ],

        //     4 =>[
        //         'SELLUOM' => 'selluom',
        //         'SKUNUMBER' => 'sku_number',
        //         'PRODCLASS' => 'prod_class',
        //         'QTYINSELLUOM' => 'qty_in_selluom',
        //         'AVGSELLPRICE' => 'avg_sell_price',
        //         'PRIMARYPRODCAT' => 'primary_prod_cat',
        //         'STAPLESOWNBRAND' => 'staple_own_brand',
        //         'ITEMDESCRIPTION' => 'item_description',
        //         'SECONDARYPRODCAT' => 'secondary_prod_cat',
        //         'STAPLESADVANTAGEITEMDESCRIPTION' => 'staples_advantages_item_description',
        //     ],
             
        //     5 =>[
        //         'UOM' => 'uom',
        //         'WB QPU' => 'wb_qpu',  
        //         'FullSKU' => 'full_sku',
        //         'Category' => 'category',
        //         'Unit Price' => 'unit_price',
        //         'List Price' => 'list_price',
        //         'ProductCode' => 'product_code',
        //         'Manufacturer' => 'manufacturer',
        //         'ITEM DESCRIPTION' => 'item_description',
        //         'Category Umbrella' => 'category_umbrella',
        //     ]
        // ];

        // for ($i=0; $i <count($supplierId) ; $i++) {
        //     $curruentSupplierId = $supplierId[$i];
        //     $query = CatalogDetail::query()
        //     ->leftJoin('catalog', 'catalog.id', '=', 'catalog_details.catalog_id')
        //     ->select('catalog_details.catalog_id as id', 'catalog_details.table_key as table_key', 'catalog_details.table_value as table_value');
        //     $query->where('catalog.supplier_id', '=', $curruentSupplierId);
    
        //     $chunkSize = 1000*count($catelogTableKeyArray[$curruentSupplierId]);
        //     $query->chunk($chunkSize, function ($catalogDetails)  use ($catelogTableKeyArray, $curruentSupplierId, $tableName) {
        //         $formatuserdata = $finalArray = [];

        //         /** Process each chunk of catalog details here */
        //         foreach ($catalogDetails as $catalogDetail) {
        //             $formatuserdata[$catalogDetail->id][] = [
        //                 'table_key' => $catalogDetail->table_key,
        //                 'table_value' => $catalogDetail->table_value,
        //             ];
        //             /** Process each catalog detail */
        //             /** For example, you can access properties like $catalogDetail->catalog_id, $catalogDetail->table_key, etc. */
        //         }

        //         foreach ($formatuserdata as $key => $value) {
        //             $finalArray[$key]['created_at'] = Carbon::now()->format('Y-m-d H:i:s');
        //             $finalArray[$key]['updated_at'] = Carbon::now()->format('Y-m-d H:i:s');

        //             for ($i=0; $i < count($value); $i++) {
        //                 $finalArray[$key][$catelogTableKeyArray[$curruentSupplierId][trim($value[$i]['table_key'])]] = $value[$i]['table_value'];
        //             }
        //         }
                
        //         DB::table($tableName[$curruentSupplierId])->insert($finalArray);  
        //     });
        // }

        ini_set('memory_limit', '1024M');
        $query = DB::table('orders')
        ->select('orders.id as id', 'order_product_details.value as amount')
        ->leftJoin('order_product_details', 'orders.id', '=', 'order_product_details.order_id')
        ->whereIn('order_product_details.key', ['Total Spend'])
        ->where('orders.supplier_id', 3)
        ->get();
        foreach ($query as $key => $value) {
            $id = DB::table('orders')
            ->where('id', $value->id)
            ->update(['amount' => $value->amount]);
        }

        // $query = DB::table('orders')
        // ->select('orders.id as id', 'orders.date as date')
        // ->whereBetween('orders.date', ['2023-12-01', '2023-12-31'])
        // ->where('orders.supplier_id', 2)
        // ->get();
        
        // // dd($query);

        // foreach ($query as $key => $value) {
        //     /** Delete records from OrderDetails table */
        //     DB::table('order_details')->where('order_id', $value->id)->delete();
            
        //     /** Delete records from ExcelData table */
        //     DB::table('order_product_details')->where('order_id', $value->id)->delete();

        //     /** Delete records from Order table */
        //     DB::table('orders')->where('id', $value->id)->delete();
        // }
    }
}
