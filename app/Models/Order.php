<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $table = 'orders';
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'date',
        'amount',
        'data_id',
        'created_by',
        'supplier_id',
        'customer_number',
    ];

    public function orderProductDetail() {
        return $this->hasMany(ExcelData::class);
    }

    public static function getFilterdData($filter = [], $csv=false){
        $query = self::query() // Replace YourModel with the actual model you are using for the data   
        ->select('order_product_details.order_id')
        ->whereIn('key', ['Line Total', 'Total Invoice Price'])
        ->leftJoin('master_account_detail', 'orders.customer_number', '=', 'master_account_detail.account_number')
        ->leftJoin('order_product_details', 'orders.id', '=', 'order_product_details.order_id');

        if (isset($filter['account_name']) && !empty($filter['account_name'])) {
            $query->where('master_account_detail.account_name', $filter['account_name']);
        } else {
            return [
                'data' => [],
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
            ];
        }

        $query->orderBy(DB::raw('CAST(`value` AS DECIMAL(10,2))'), 'desc')->limit(100);
        $queryData = $query->get()->toArray();

        $indexedArray = [];
        foreach ($queryData as $value) {
            $indexedArray[] = $value['order_id'];
        }
    
        $query1 = DB::table('order_product_details')
            ->select('order_product_details.*')
            ->whereIn('order_product_details.order_id', $indexedArray);

        $filteredData = $query1->get();
        foreach ($filteredData as $key => $value) {
            $formatuserdata[$value->order_id][] = [
                'key' => $value->key,
                'value' => $value->value,
            ];
        }
        // echo"<pre>";
        // print_r($formatuserdata);
        // die;
        if (isset($formatuserdata) && !empty($formatuserdata)) {
            $arrayKey=0;
            foreach ($formatuserdata as $key => $value) {
                for ($i=0; $i < count($value); $i++) {
                    if (in_array($value[$i]['key'], ['Item Num','SOLD TOACCOUNT','Invoice Number','SKUNUMBER','SKU'])) {
                        $finalArray[$arrayKey]['sku'] = $value[$i]['value'];
                    }
    
                    if (in_array($value[$i]['key'], ['Uo M','Track Code','SHIPTOZIPCODE','UOM','SELLUOM'])) {
                        $finalArray[$arrayKey]['uom'] = $value[$i]['value'];
                    }
                    
                    if (in_array($value[$i]['key'], ['Category','CATEGORIES','Material Segment','TRANSTYPECODE','CLASS'])) {
                        $finalArray[$arrayKey]['category'] = $value[$i]['value'];
                    }
    
                    if (in_array($value[$i]['key'], ['Item Name','DESCRIPTION','Material Description','STAPLESADVANTAGEITEMDESCRIPTION','Product Description'])) {
                        $finalArray[$arrayKey]['description'] = $value[$i]['value'];
                    }
    
                    if (in_array($value[$i]['key'], ['Qty','QUANTITYSHIPPED','Billing Qty','QTY','QTY Shipped'])) {
                        $finalArray[$arrayKey]['quantity_purchased'] = $value[$i]['value'];
                    }
    
                    if (in_array($value[$i]['key'], ['Line Total','ON-CORE
                    SPEND','Total Invoice Price','Total Spend','AVGSELLPRICE'])) {
                        $finalArray[$arrayKey]['total_spend'] = $value[$i]['value'];
                    }
    
                    if (in_array($value[$i]['key'], ['Ext Price','Actual Price Paid','Unit Net Price','ITEMFREQUENCY'])) {
                        $finalArray[$arrayKey]['last_of_unit_net_price'] = $value[$i]['value'];
                    }
    
                    if (in_array($value[$i]['key'], ['Price','OFF-CORE
                    SPEND','Reference Price','(Unit) Web Price','ADJGROSSSALES'])) {
                        $finalArray[$arrayKey]['web_price'] = $value[$i]['value'];
                    }
    
                    if (in_array($value[$i]['key'], ['Uo M','SHIP TOACCOUNT','Track Code','SHIPTOZIPCODE'])) {
                        $finalArray[$arrayKey]['savings_percentage'] = '';
                    }
                }
                $arrayKey++;
            }

            foreach($finalArray as $key => $value){
                if ($value['web_price'] != 0) {
                    $finalArray[$key]['savings_percentage'] = number_format(($value['web_price'] - $value['last_of_unit_net_price'])/($value['web_price']), 2).'%';
                }
            }
        } else {
            $finalArray=[];
        }
        
        // echo"<pre>";
        // print_r($finalArray);
        // die;
        usort($finalArray, function($a, $b) {
            return $b['total_spend'] <=> $a['total_spend']; // Compare prices in descending order
        });

        $totalRecords = count($finalArray);
        if ($csv == true) {
            $finalArray['heading'] = ['SKU', 'Category', 'Description', 'Uom', 'Savings Percentage', 'Quantity Purchased', 'Web Price', 'Last Of Unit Net Price', 'Total Spend'];
            return $finalArray;
        } else {
            // Return the result along with total and filtered counts
            return [
                'data' => $finalArray,
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $totalRecords,
            ];
        }
    }

    public static function getSupplierReportFilterdData($filter = [], $csv = false)
    {
        $orderColumnArray = [
            0=>'suppliers.supplier_name',
            1=>'master_account_detail.account_name',
            2=>'amount',
            3=>'rebate.volume_rebate',
            4=>'rebate.incentive_rebate',
            5=>'orders.date',
        ];

        $query = self::query()->selectRaw("SUM(`orders`.`amount`) AS `amount`, 
        `master_account_detail`.`account_name` AS `account_name`,
        (orders.amount / 100) * `rebate`.`volume_rebate` AS `volume_rebate`,
        (orders.amount / 100) * `rebate`.`incentive_rebate` AS `incentive_rebate`, 
        `suppliers`.`supplier_name` AS `supplier_name`, 
        `orders`.`date` AS `date`")
            ->leftJoin('master_account_detail', 'orders.customer_number', '=', 'master_account_detail.account_number')
            ->leftJoin('rebate', function ($join) {
                $join->on(DB::raw('CAST(orders.customer_number AS SIGNED)'), '=', DB::raw('CAST(rebate.account_number AS SIGNED)'));
            })
            ->leftJoin('suppliers', 'suppliers.id', '=', 'orders.supplier_id')
            ->leftJoin('order_details', 'orders.id', '=', 'order_details.order_id');
    
        if (isset($filter['supplier']) && !empty($filter['supplier'])) {
            $query->where('orders.supplier_id', $filter['supplier']);
        } else {
            return [
                'data' => [],
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
            ];
        }
    
        if (isset($filter['dates']) && !empty($filter['dates'])) {
            $dates = explode(" - ", $filter['dates']);
            $startDate = date_format(date_create(trim($dates[0])), 'Y-m-d H:i:s');
            $endDate = date_format(date_create(trim($dates[1])), 'Y-m-d H:i:s');
            $query->whereBetween('orders.date', [$startDate, $endDate]);
        }
    
        $query->groupBy('master_account_detail.account_name');
        $totalRecords = $query->getQuery()->getCountForPagination();

        /** Get total records count (without filtering) */
        if (isset($filter['order'][0]['column']) && isset($orderColumnArray[$filter['order'][0]['column']]) && isset($filter['order'][0]['dir'])) {
            /** Order by column and direction */
            $query->orderBy($orderColumnArray[$filter['order'][0]['column']], $filter['order'][0]['dir']);
        } else {
            $query->orderBy($orderColumnArray[0], 'asc');
        }

        // $totalVolumeRebate = $query->sum(DB::raw('(orders.amount / 100) * rebate.volume_rebate'));
        // $totalIncentiveRebate = $query->sum(DB::raw('(orders.amount / 100) * rebate.incentive_rebate'));
        $totalVolumeRebate = $totalIncentiveRebate = 0;
        foreach ($query->get() as $key => $value) {
            $totalVolumeRebate += $value->volume_rebate;
            $totalIncentiveRebate += $value->incentive_rebate;
        }

        $formatuserdata = $query->when(isset($filter['start']) && isset($filter['length']), function ($query) use ($filter) {
            return $query->skip($filter['start'])->take($filter['length']);
        })->get();
        
        $finalArray=[];
        if (isset($formatuserdata) && !empty($formatuserdata)) {
            foreach ($formatuserdata as $key => $value) {
                $finalArray[$key]['supplier'] = $value->supplier_name;
                $finalArray[$key]['account_name'] = $value->account_name;
                $finalArray[$key]['amount'] = '$'.$value->amount;
                $finalArray[$key]['volume_rebate'] = '<input type="hidden" value="'.$totalVolumeRebate.'"class="input_volume_rebate"> $'.number_format(($value->amount/100)*$value->volume_rebate, 2);
                $finalArray[$key]['incentive_rebate'] = '<input type="hidden" value="'.$totalIncentiveRebate.'" class="input_incentive_rebate"> $'.number_format(($value->amount/100)*$value->incentive_rebate, 2);
                // $finalArray[$key]['volume_rebate'] = '<input type="hidden" value="'.$totalVolumeRebate.'"class="input_volume_rebate">'.(!empty($value->volume_rebate) ? ($value->volume_rebate.'%') : (''));
                // $finalArray[$key]['incentive_rebate'] = '<input type="hidden" value="'.$totalIncentiveRebate.'" class="input_incentive_rebate">'.((!empty($value->incentive_rebate)) ? ($value->incentive_rebate.'%') : (''));
                $finalArray[$key]['date'] = date_format(date_create($value->date), 'm/d/Y');
                // $finalArray[$key]['start_date'] = date_format(date_create($filter['start_date']), 'Y-m-d H:i:s');
                // $finalArray[$key]['end_date'] = date_format(date_create($filter['end_date']), 'Y-m-d H:i:s');
            }
        }
    
        if ($csv) {
            $finalArray['heading'] = ['Total Spend', 'SKU', 'Description', 'Category', 'Uom', 'Savings Percentage', 'Quantity Purchased', 'Web Price', 'Last Of Unit Net Price'];
            return $finalArray;
        } else {
            return [
                'data' => $finalArray,
                'recordsTotal' => $totalRecords, // Use count of formatted data for total records
                'recordsFiltered' => $totalRecords, // Use total records from the query
            ];
        }
    }
    

}
