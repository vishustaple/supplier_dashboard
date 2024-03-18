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
                    if (in_array($value[$i]['key'], ['Item Num','SOLD TOACCOUNT','Invoice Number','SKUNUMBER','MASTER_CUSTOMER'])) {
                        $finalArray[$arrayKey]['sku'] = $value[$i]['value'];
                    }
    
                    if (in_array($value[$i]['key'], ['Uo M','SHIP TOACCOUNT','Track Code','SHIPTOZIPCODE'])) {
                        $finalArray[$arrayKey]['uom'] = $value[$i]['value'];
                    }
                    
                    if (in_array($value[$i]['key'], ['Category','CATEGORIES','Material Segment','ORDERCONTACT'])) {
                        $finalArray[$arrayKey]['category'] = $value[$i]['value'];
                    }
    
                    if (in_array($value[$i]['key'], ['Item Name','DESCRIPTION','Material Description','ITEMDESCRIPTION','STAPLESADVANTAGEITEMDESCRIPTION'])) {
                        $finalArray[$arrayKey]['description'] = $value[$i]['value'];
                    }
    
                    if (in_array($value[$i]['key'], ['Qty','QUANTITYSHIPPED','Billing Qty','QTY'])) {
                        $finalArray[$arrayKey]['quantity_purchased'] = $value[$i]['value'];
                    }
    
                    if (in_array($value[$i]['key'], ['Line Total','Total Invoice Price'])) {
                        $finalArray[$arrayKey]['total_spend'] = $value[$i]['value'];
                    }
    
                    if (in_array($value[$i]['key'], ['Ext Price','ON-CORE
                    SPEND','Actual Price Paid'])) {
                        $finalArray[$arrayKey]['last_of_unit_net_price'] = $value[$i]['value'];
                    }
    
                    if (in_array($value[$i]['key'], ['Price','OFF-CORE
                    SPEND','Reference Price'])) {
                        $finalArray[$arrayKey]['web_price'] = $value[$i]['value'];
                    }
    
                    if (in_array($value[$i]['key'], ['Uo M','SHIP TOACCOUNT','Track Code','SHIPTOZIPCODE'])) {
                        $finalArray[$arrayKey]['savings_percentage'] = '';
                    }
                }
                $arrayKey++;
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
            $finalArray['heading'] = ['Total Spend', 'SKU', 'Description', 'Category', 'Uom', 'Savings Percentage', 'Quantity Purchased', 'Web Price', 'Last Of Unit Net Price'];
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

    public static function getSupplierReportFilterdData($filter = [], $csv=false){
        $query = self::query() // Replace YourModel with the actual model you are using for the data   
        ->select('orders.amount as amount',
            'master_account_detail.account_name as account_name',
            'rebate.volume_rebate as volume_rebate',
            'rebate.incentive_rebate as incentive_rebate',
            'suppliers.supplier_name as supplier_name',
        )

        ->leftJoin('master_account_detail', 'orders.customer_number', '=', 'master_account_detail.account_number')
        ->leftJoin('rebate', 'orders.customer_number', '=', 'rebate.account_number')
        ->leftJoin('suppliers', 'suppliers.id', '=', 'orders.supplier_id')
        ->leftJoin('order_details', 'orders.id', '=', 'order_details.order_id');

        if (isset($filter['supplier']) && !empty($filter['supplier'])) {
            $query->where('orders.supplier_id', $filter['supplier']);
        }

        // Filter data based on request parameters
        if (isset($filter['start_date']) && !empty($filter['start_date']) && isset($filter['end_date']) && !empty($filter['end_date'])) {
            $startDate = date_format(date_create($filter['start_date']), 'Y-m-d H:i:s');
            $endDate = date_format(date_create($filter['end_date']), 'Y-m-d H:i:s');
            // Debug output
            // dd('Start Date: ' . $startDate, 'End Date: ' . $endDate);
            $query->whereBetween('orders.date', [$startDate, $endDate]);
        }

        $formatuserdata = $query->get();
        $finalArray=[];
        if (isset($formatuserdata) && !empty($formatuserdata)) {
            foreach ($formatuserdata as $key => $value) {
                $finalArray[$key]['supplier'] = $value->supplier_name;
                $finalArray[$key]['account_name'] = $value->account_name;
                $finalArray[$key]['amount'] = '$'.$value->amount;
                // $finalArray[$key]['volume_rebate'] = ($value->amount/100)*$value->volume_rebate;
                // $finalArray[$key]['incentive_rebate'] = ($value->amount/100)*$value->incentive_rebate;
                $finalArray[$key]['volume_rebate'] = (!empty($value->volume_rebate)) ? ($value->volume_rebate.'%') : ('');
                $finalArray[$key]['incentive_rebate'] = (!empty($value->incentive_rebate)) ? ($value->incentive_rebate.'%') : ('');
                $finalArray[$key]['start_date'] = date_format(date_create($filter['start_date']), 'Y-m-d H:i:s');
                $finalArray[$key]['end_date'] = date_format(date_create($filter['end_date']), 'Y-m-d H:i:s');
            }
        }

        // echo"<pre>";
        // print_r($finalArray);
        // die;
        // usort($finalArray, function($a, $b) {
        //     return $b['total_spend'] <=> $a['total_spend']; // Compare prices in descending order
        // });

        $totalRecords = count($finalArray);
        if ($csv == true) {
            $finalArray['heading'] = ['Total Spend', 'SKU', 'Description', 'Category', 'Uom', 'Savings Percentage', 'Quantity Purchased', 'Web Price', 'Last Of Unit Net Price'];
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
}
