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
        'created_by',
        'supplier_id',
        'date',
        'customer_number',
    ];


    public static function getFilterdData($filter = [], $csv=false){
        $orderColumnArray = [
            0 => 'orders.customer_number',
            1 => "accounts.alies",
            2 => 'suppliers.supplier_name',
            3 => 'orders.amount',
        ];
    
        $query = self::query() // Replace YourModel with the actual model you are using for the data
        ->leftJoin('suppliers', 'orders.supplier_id', '=', 'suppliers.id')
        ->leftJoin('accounts', 'orders.customer_number', '=', 'accounts.customer_number')

        ->select('orders.id', 'orders.amount as amount', 'suppliers.supplier_name as supplier_name', 'orders.customer_number as customer_number', 'accounts.alies as customer_name'); // Adjust the column names as needed
       
        // Filter data based on request parameters
        if (isset($filter['start_date']) && !empty($filter['start_date']) && isset($filter['end_date']) && !empty($filter['end_date'])) {
            $startDate = date_format(date_create($filter['start_date']), 'Y-m-d H:i:s');
            $endDate = date_format(date_create($filter['end_date']), 'Y-m-d H:i:s');
            // Debug output
            // dd('Start Date: ' . $startDate, 'End Date: ' . $endDate);
            $query->whereBetween('orders.date', [$startDate, $endDate]);
        }
        
        if (isset($filter['supplierId']) && !empty($filter['supplierId'])) {
            $query->where('orders.supplier_id', $filter['supplierId']);
        }

        // Search functionality
        if (isset($filter['search']['value']) && !empty($filter['search']['value'])) {
            $searchTerm = $filter['search']['value'];

            $query->where(function ($q) use ($searchTerm, $orderColumnArray) {
                foreach ($orderColumnArray as $column) {
                    $q->orWhere($column, 'LIKE', '%' . $searchTerm . '%');
                }
            });
        }

        
        // Get total records count (without filtering)
        
        // $totalRecords = $query->count();
        // $totalRecords = $query->getCountForPagination();
        $query->groupBy('orders.id');

        $totalRecords = $query->getQuery()->getCountForPagination();
        
        if (isset($filter['order'][0]['column']) && isset($orderColumnArray[$filter['order'][0]['column']]) && isset($filter['order'][0]['dir'])) {
            // Order by column and direction
            $query->orderBy($orderColumnArray[$filter['order'][0]['column']], $filter['order'][0]['dir']);
        } else {
            $query->orderBy($orderColumnArray[0], 'asc');
        }
        // dd($query->toSql());    
        
        if (isset($filter['start']) && isset($filter['length'])) {
            // Get paginated results based on start, length
            $filteredData = $query->skip($filter['start'])->take($filter['length'])->get();
        } else {
            $filteredData = $query->get();
        }
        // dd($query->toSql());    
        
        // echo"<pre>";
        // print_r($filteredData);die;
        // Print the SQL query

        // Get filtered records count
        // $filteredRecords = $query->count();
        
        $formatuserdata=[];
        foreach ($filteredData as $key => $data) {
            $formatuserdata[$key]['amount'] = '$'.$data->amount;
            $formatuserdata[$key]['customer_name'] = $data->customer_name;
            $formatuserdata[$key]['supplier_name'] = $data->supplier_name;
            $formatuserdata[$key]['customer_number'] = $data->customer_number;
            if ($csv == false) {    
                $formatuserdata[$key]['id'] = '<a class="btn btn-primary" title="View Details" href= '.route('report.type', ['reportType' => 'business_report','id' => $data->id]).'><i class="fa-regular  fa-eye"></i></a>';
            }
            // $formatuserdata[$key]['date'] = date_format(date_create($data->date), 'm/d/Y');
        }

        if ($csv == true) {
            return $formatuserdata;
        } else {
            // Return the result along with total and filtered counts
            return [
                'data' => $formatuserdata,
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $totalRecords,
            ];
        }
    }
}
