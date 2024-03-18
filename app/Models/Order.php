<?php

namespace App\Models;

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
        ->leftJoin('master_account_detail', 'orders.customer_number', '=', 'master_account_detail.account_number')
        ->leftJoin('order_product_details', 'orders.id', '=', 'order_product_details.order_id')

        ->select('order_product_details.*', 'orders.id as order_id'); // Adjust the column names as needed
       
    
        if (isset($filter['account_name']) && !empty($filter['account_name'])) {
            $query->where('master_account_detail.account_name', $filter['account_name']);
        }
      
        $filteredData = $query->get();
        
        foreach ($filteredData->toArray() as $key => $value) {
            $formatuserdata[$value['order_id']][] = [
                'key' => $value['key'],
                'value' => $value['value'],
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
    
                    if (in_array($value[$i]['key'], ['Uo M'])) {
                        $finalArray[$arrayKey]['savings_percentage'] = '';
                    }
                }
                $arrayKey++;
            }
            $finalArray1=[];
            foreach ($finalArray as $key => $value) {
                if (100 >= count($finalArray1)) {
                    $finalArray1[$arrayKey]['sku'] = $value['sku'];
                    $finalArray1[$arrayKey]['uom'] = $value['uom'];
                    $finalArray1[$arrayKey]['category'] = $value['category'];
                    $finalArray1[$arrayKey]['description'] = $value['description'];
                    $finalArray1[$arrayKey]['quantity_purchased'] = $value['quantity_purchased'];
                    $finalArray1[$arrayKey]['total_spend'] = $value['total_spend'];
                    $finalArray1[$arrayKey]['last_of_unit_net_price'] = $value['last_of_unit_net_price'];
                    $finalArray1[$arrayKey]['web_price'] = $value['web_price'];
                    $finalArray1[$arrayKey]['savings_percentage'] = $value['savings_percentage'];
                    $arrayKey++;
                }
            }
            usort($finalArray1, function($a, $b) {
                return $b['total_spend'] <=> $a['total_spend']; // Compare prices
            });
        } else {
            $finalArray=[];
            $finalArray1=[];
        }
        
        $arrayKey=0;
        if ($filter['start'] > 0) {
            $start = $filter['start']-1;
        } else {
            $start = $filter['start'];
        }
        // print_r($filter['length']);
        // print_r(count($finalArray));
        // die;
        
        // echo"<pre>";
        // print_r($finalArray);
        // die;
        
        $totalRecords = count($finalArray1);
        if ($csv == true) {
            return $finalArray1;
        } else {
            // Return the result along with total and filtered counts
            return [
                'data' => $finalArray1,
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $totalRecords,
            ];
        }
    }
}
