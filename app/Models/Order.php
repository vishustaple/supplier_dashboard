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
        $orderColumnArray = [
            0 => 'orders.customer_number',
            1 => "master_account_detail.alies",
            2 => 'suppliers.supplier_name',
            3 => 'orders.amount',
        ];
    
        $query = self::query() // Replace YourModel with the actual model you are using for the data
        ->leftJoin('master_account_detail', 'orders.customer_number', '=', 'master_account_detail.account_number')
        ->leftJoin('order_product_details', 'orders.id', '=', 'order_product_details.order_id')

        ->select('order_product_details.*', 'orders.id as order_id'); // Adjust the column names as needed
       
        // Filter data based on request parameters
        // if (isset($filter['start_date']) && !empty($filter['start_date']) && isset($filter['end_date']) && !empty($filter['end_date'])) {
        //     $startDate = date_format(date_create($filter['start_date']), 'Y-m-d H:i:s');
        //     $endDate = date_format(date_create($filter['end_date']), 'Y-m-d H:i:s');
        //     // Debug output
        //     // dd('Start Date: ' . $startDate, 'End Date: ' . $endDate);
        //     $query->whereBetween('orders.date', [$startDate, $endDate]);
        // }
        
        if (isset($filter['account_number']) && !empty($filter['account_number'])) {
            $query->where('orders.customer_number', $filter['account_number']);
        }
        
        // $query->orWhere('order_product_details.key', 'LIKE', '%SKU%');
        // $query->orWhere('order_product_details.key', 'LIKE', '%Description%');
        // $query->orWhere('order_product_details.key', 'LIKE', '%UOM (Unit of Measure)%');
        // $query->orWhere('order_product_details.key', 'LIKE', '%Category%');
        // $query->orWhere('order_product_details.key', 'LIKE', '%Quantity Purchased%');
        // $query->orWhere('order_product_details.key', 'LIKE', '%Total Spend%');
        // $query->orWhere('order_product_details.key', 'LIKE', '%Last Of Unit Net Price%');
        // $query->orWhere('order_product_details.key', 'LIKE', '%Web Price%');
        // $query->orWhere('order_product_details.key', 'LIKE', '%Savings Percentage%');
        // Search functionality
        // if (isset($filter['search']['value']) && !empty($filter['search']['value'])) {
        //     $searchTerm = $filter['search']['value'];

        //     $query->where(function ($q) use ($searchTerm, $orderColumnArray) {
        //         foreach ($orderColumnArray as $column) {
        //             $q->orWhere($column, 'LIKE', '%' . $searchTerm . '%');
        //         }
        //     });
        // }

        
        // Get total records count (without filtering)
        
        // $totalRecords = $query->count();
        // $totalRecords = $query->getCountForPagination();
        // $query->groupBy('orders.id');

        // $totalRecords = $query->getQuery()->getCountForPagination();
        
        // if (isset($filter['order'][0]['column']) && isset($orderColumnArray[$filter['order'][0]['column']]) && isset($filter['order'][0]['dir'])) {
        //     // Order by column and direction
        //     $query->orderBy($orderColumnArray[$filter['order'][0]['column']], $filter['order'][0]['dir']);
        // } else {
        //     $query->orderBy($orderColumnArray[0], 'asc');
        // }
        // dd($query->toSql());    
        
        // if (isset($filter['start']) && isset($filter['length'])) {
        //     // Get paginated results based on start, length
        //     $filteredData = $query->skip($filter['start'])->take($filter['length'])->get();
        // } else {
            $filteredData = $query->get();
        // }
        // dd($query->toSql());    
        
        // echo"<pre>";
        // print_r($filteredData);die;
        // Print the SQL query

        // Get filtered records count
        // $filteredRecords = $query->count();
        
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
                    if (in_array($value[$i]['key'], ['Item Num'])) {
                        $finalArray[$arrayKey]['sku'] = $value[$i]['value'];
                    }
    
                    if (in_array($value[$i]['key'], ['Uo M'])) {
                        $finalArray[$arrayKey]['uom'] = $value[$i]['value'];
                    }
                    
                    if (in_array($value[$i]['key'], ['Category'])) {
                        $finalArray[$arrayKey]['category'] = $value[$i]['value'];
                    }
    
                    if (in_array($value[$i]['key'], ['Item Name'])) {
                        $finalArray[$arrayKey]['description'] = $value[$i]['value'];
                    }
    
                    if (in_array($value[$i]['key'], ['Qty'])) {
                        $finalArray[$arrayKey]['quantity_purchased'] = $value[$i]['value'];
                    }
    
                    if (in_array($value[$i]['key'], ['Line Total'])) {
                        $finalArray[$arrayKey]['total_spend'] = $value[$i]['value'];
                    }
    
                    if (in_array($value[$i]['key'], ['Ext Price'])) {
                        $finalArray[$arrayKey]['last_of_unit_net_price'] = $value[$i]['value'];
                    }
    
                    if (in_array($value[$i]['key'], ['Price'])) {
                        $finalArray[$arrayKey]['web_price'] = $value[$i]['value'];
                    }
    
                    if (in_array($value[$i]['key'], ['Uo M'])) {
                        $finalArray[$arrayKey]['savings_percentage'] = '';
                    }
                }
                $arrayKey++;
            }
        } else {
            $finalArray=[];
        }

        // $finalArray['heading'] = $keyArray;
        // echo"<pre>";
        // print_r($finalArray);
        // die;
        $totalRecords = count($finalArray);
        if ($csv == true) {
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
