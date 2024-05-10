<?php

namespace App\Console\Commands;

use Illuminate\Support\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
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
        $years = ['2020', '2021', '2022', '2023', '2024'];
        $filter['core'] = 1;

        $query = DB::table('master_account_detail')->select('master_account_detail.account_name as account_name');
        $query->whereNotNull('master_account_detail..account_name');
        $query->groupBy('master_account_detail.account_name');
        $query->where('master_account_detail.category_supplier', 3);
        $account = $query->get();
      
        foreach ($account as $accounts) {
            foreach ($years as $year) {
                $res[1] =[
                    'January',
                    'February',
                    'March',
                ];
        
                $res[2] = [
                    'April',
                    'May',
                    'June',
                ];
        
                $res[3] = [
                    'July',
                    'August',
                    'September',
                ];
        
                $res[4] = [
                    'October',
                    'November',
                    'December',
                ];
        
                $monthDates = [];
        
                for ($month = 1; $month <= 12; $month++) {
                    $start = date('Y-m-01', strtotime("$year-$month-01"));
                    $end = date('Y-m-t', strtotime("$year-$month-01"));
            
                    $monthDates[] = [
                        'start_date' => $start,
                        'end_date' => $end,
                    ];
                }
        
                $startDate1 = $monthDates[0]['start_date'];
                $endDate1 = $monthDates[2]['end_date'];
                
                $startDate2 = $monthDates[3]['start_date'];
                $endDate2 = $monthDates[5]['end_date'];
                
                $startDate3 = $monthDates[6]['start_date'];
                $endDate3 = $monthDates[8]['end_date'];
                
                $startDate4 = $monthDates[9]['start_date'];
                $endDate4 = $monthDates[11]['end_date'];
                $query = Order::query() // Replace YourModel with the actual model you are using for the data
                ->select('orders.id as id') 
                ->leftJoin('order_product_details', 'orders.id', '=', 'order_product_details.order_id')
                ->leftJoin('master_account_detail', 'orders.customer_number', '=', 'master_account_detail.account_number');
                $query->whereIn('key', ['Core Flag']);
    
                if ($filter['core'] == 1) {
                    $query->whereIn('value', ['N', 'U']);
                } else {
                    $query->whereIn('value', ['Y']);
                }
                
                $query->whereYear('orders.date', $year);
                $query->where('master_account_detail.account_name', $accounts->account_name);
                $query->groupBy('order_product_details.order_id');
                $query->orderBy('orders.amount', 'desc')->limit(100);
    
                $orderIdArray = [];
    
                if ($query->get()) {
                    foreach ($query->get() as $key => $value) {
                        $orderIdArray[] = $value->id;
                    }
                }
    
                if (!empty($orderIdArray)) {
                    $query1 = Order::query()
                    ->select(
                        'orders.date as date',
                        'orders.amount as total_spend',
                        DB::raw("MAX(CASE WHEN `key` = 'UOM' THEN `value` ELSE NULL END) AS uom"),
                        DB::raw("MAX(CASE WHEN `key` = 'SKU' THEN `value` ELSE NULL END) AS sku"),
                        DB::raw("MAX(CASE WHEN `key` = 'Product Description' THEN `value` ELSE NULL END ) AS description"),
                        DB::raw("MAX(CASE WHEN `key` = 'QTY Shipped' THEN `value` ELSE NULL END) AS quantity_purchased"),
                        DB::raw("MAX(CASE WHEN `key` = 'Unit Net Price' THEN `value` ELSE NULL END) AS unit_price"),
                        DB::raw("MAX(CASE WHEN `key` = '(Unit) Web Price' THEN `value` ELSE NULL END) AS web_price"),
                    )
    
                    ->leftJoin('order_product_details', 'orders.id', '=', 'order_product_details.order_id')
                    ->whereIn('orders.id', $orderIdArray)
                    ->groupBy('order_product_details.order_id')
                    ->orderBy('orders.amount', 'desc');
    
                    $queryData = $query1->get();
                    // dd($queryData);
                    $newFinalArray = [];
                    foreach ($queryData as $key => $value) {
                        $query = DB::table('office_depot_order')->where('sku', $value->sku)
                        ->selectRaw(
                            'CASE WHEN `office_depot_order`.`shipped_date` BETWEEN ? AND ? THEN `office_depot_order`.`unit_net_price` ELSE 0 END AS unit_price_q1_price,
                            CASE WHEN `office_depot_order`.`shipped_date` BETWEEN ? AND ? THEN `office_depot_order`.`unit_net_price` ELSE 0 END AS unit_price_q2_price,
                            CASE WHEN `office_depot_order`.`shipped_date` BETWEEN ? AND ? THEN `office_depot_order`.`unit_net_price` ELSE 0 END AS unit_price_q3_price,
                            CASE WHEN `office_depot_order`.`shipped_date` BETWEEN ? AND ? THEN `office_depot_order`.`unit_net_price` ELSE 0 END AS unit_price_q4_price,
                            CASE WHEN `office_depot_order`.`shipped_date` BETWEEN ? AND ? THEN `office_depot_order`.`unit_web_price` ELSE 0 END AS web_price_q1_price,
                            CASE WHEN `office_depot_order`.`shipped_date` BETWEEN ? AND ? THEN `office_depot_order`.`unit_web_price` ELSE 0 END AS web_price_q2_price,
                            CASE WHEN `office_depot_order`.`shipped_date` BETWEEN ? AND ? THEN `office_depot_order`.`unit_web_price` ELSE 0 END AS web_price_q3_price,
                            CASE WHEN `office_depot_order`.`shipped_date` BETWEEN ? AND ? THEN `office_depot_order`.`unit_web_price` ELSE 0 END AS web_price_q4_price,
                            MIN(CASE WHEN `office_depot_order`.`shipped_date` BETWEEN ? AND ? THEN `office_depot_order`.`total_spend` ELSE 0 END) AS lowest_price',
                            [$startDate1,$endDate1,$startDate2,$endDate2,$startDate3,$endDate3,$startDate4,$endDate4,$startDate1,$endDate1,$startDate2,$endDate2,$startDate3,$endDate3,$startDate4,$endDate4,$startDate1,$endDate4]
                        );
    
                        if ($filter['core'] == 1) {
                            $query->whereIn('core_flag', ['N', 'U']);
                        } else {
                            $query->whereIn('core_flag', ['Y']);
                        }
                    
                        $dataPrice = $query->first();
                        $newFinalArray[] = [
                            'supplier' => 3,
                            'sku' => $value->sku,
                            'account_name' => $accounts->account_name,
                            'description' => $value->description,
                            'product_type' => $filter['core'],
                            'uom' => $value->uom,
                            'category' => '',
                            'quantity_purchased' => $value->quantity_purchased,
                            'total_spend' => $value->total_spend,
                            'unit_price_q1_price' => $dataPrice->unit_price_q1_price,
                            'unit_price_q2_price' => $dataPrice->unit_price_q2_price,
                            'unit_price_q3_price' => $dataPrice->unit_price_q3_price,
                            'unit_price_q4_price' => $dataPrice->unit_price_q4_price,
                            'web_price_q1_price' => $dataPrice->web_price_q1_price,
                            'web_price_q2_price' => $dataPrice->web_price_q2_price,
                            'web_price_q3_price' => $dataPrice->web_price_q3_price,
                            'web_price_q4_price' => $dataPrice->web_price_q4_price,
                            'lowest_price' => $dataPrice->lowest_price,
                            'date' => $value->date,
                            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                            'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                        ];
                        DB::table('od_report')->insert($newFinalArray);
                        // print_r($newFinalArray);
                    }
                }
            }
        }
        // dd($newFinalArray);
    }
}
