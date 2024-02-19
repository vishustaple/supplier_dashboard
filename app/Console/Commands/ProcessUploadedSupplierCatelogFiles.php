<?php

namespace App\Console\Commands;

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
        $supplierId = [3, 4, 5];
        for ($i=0; $i <=count($supplierId) ; $i++) {
            $curruentSupplierId = $supplierId[$i]; 
            $catelogTableKeyArray = [
                3 =>[
                    'UM' => 'um',
                    'Sku Number' => 'sku_number',
                    'Vendor Prd#' => 'vendor_prd',
                    'Item' => 'item', 
                    'Platinum Price' => 'platinum_price',
                    'Platinum Price Method' => 'platinum_price_method',
                    'Preferred Price' => 'preferred_price',
                    'Preferred Price Method' => 'preferred_price_method',
                    'Dept Description' => 'dept_description',
                    'Class Description' => 'class_description',
                    'Sugg' => 'sugg',
                    'Vendor Name' => 'vendor_name',
                    'MBE' => 'mbe',
                    'WBE' => 'wbe',
                    'Recycled' => 'recycled',
                ],

                4 =>[
                    'selluom' => 'selluom',
                    'skunumber' => 'sku_number',
                    'prodclass' => 'prod_class',
                    'qtyinselluom' => 'qty_in_selluom',
                    'avgsellprice' => 'avg_sell_price',
                    'primaryprodcat' => 'primary_prod_cat',
                    'staplesownbrand' => 'staple_own_brand',
                    'itemdescription' => 'item_description',
                    'secondaryprodcat' => 'secondary_prod_cat',
                    'staplesadvantageitemdescription' => 'staples_advantages_item_description',
                ],
                 
                5 =>[
                    
                ]
            ];
            $query = CatalogDetail::query()
            ->leftJoin('catalog', 'catalog.id', '=', 'catalog_details.catalog_id')
            ->select('catalog_details.catalog_id as id', 'catalog_details.table_key as table_key', 'catalog_details.table_value as table_value');
            $query->where('catalog.supplier_id', '=', 4);
    
            $chunkSize = 10000;
            $query->chunk($chunkSize, function ($catalogDetails)  use ($catelogTableKeyArray, $curruentSupplierId) {
                // Process each chunk of catalog details here
                foreach ($catalogDetails->toArray() as $catalogDetail) {
                    $formatuserdata[$catalogDetail['id']][] = [
                        'table_key' => $catalogDetail['table_key'],
                        'table_value' => $catalogDetail['table_value'],
                    ];
                    // Process each catalog detail
                    // For example, you can access properties like $catalogDetail->catalog_id, $catalogDetail->table_key, etc.
                }
    
                foreach ($formatuserdata as $key => $value) {
                    for ($i=0; $i < count($value); $i++) {
                        $finalArray[$key][$catelogTableKeyArray[$curruentSupplierId][trim(strtolower($value[$i]['table_key']))]] = $value[$i]['table_value'];
                        $finalArray[$key]['created_at'] = Carbon::now()->format('Y-m-d H:i:s');
                        $finalArray[$key]['updated_at'] = Carbon::now()->format('Y-m-d H:i:s');
                    }
                }
    
                DB::table('catalog_staples')->insert($finalArray);  
                unset($finalArray);
            });
        }

        // SourceModel::chunk($chunkSize, function ($sourceModels) {
        //     foreach ($sourceModels as $sourceModel) {
        //         // Map and modify data as needed before inserting into the destination table
        //         $data = [
        //             'column1' => $sourceModel->column1,
        //             'column2' => $sourceModel->column2,
        //             // Add other columns as needed
        //         ];

        //         // Insert data into the destination table
        //         DestinationModel::insert($data);
        //     }
        // });
    }
}
