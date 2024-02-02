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
            0 => 'orders.id',
            1 => 'orders.amount',
            2 => 'orders.date',
            3 => 'suppliers.supplier_name',
            4 => 'orders.customer_number',
            5 => "accounts.customer_name"
        ];
    
        $query = self::query() // Replace YourModel with the actual model you are using for the data
        ->leftJoin('suppliers', 'orders.supplier_id', '=', 'suppliers.id')
        ->leftJoin('accounts', 'orders.customer_number', '=', 'accounts.customer_number')

        ->select('orders.id as id', 'orders.amount as amount', 'orders.date as date', 'suppliers.supplier_name as supplier_name', 'orders.customer_number as customer_number', "accounts.customer_name as customer_name"); // Adjust the column names as needed

        // Filter data based on request parameters
        if (isset($filter['start_date']) && !empty($filter['start_date']) && isset($filter['end_date']) && !empty($filter['end_date'])) {
            $startDate = date_format(date_create($filter['start_date']), 'Y-m-d H:i:s');
            $endDate = date_format(date_create($filter['end_date']), 'Y-m-d H:i:s');
            // Debug output
            // dd('Start Date: ' . $startDate, 'End Date: ' . $endDate);
            $query->whereBetween('orders.date', [$startDate, $endDate]);
            // dd($query->toSql());    
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
        $totalRecords = $query->count();

        if (isset($orderColumnArray[$filter['order'][0]['column']]) && isset($filter['order'][0]['dir'])) {
            // Order by column and direction
            $query->orderBy($orderColumnArray[$filter['order'][0]['column']], $filter['order'][0]['dir']);
        }

        if (isset($filter['start']) && isset($filter['length'])) {
            // Get paginated results based on start, length
            $filteredData = $query->skip($filter['start'])->take($filter['length'])->get();
        }

        // Print the SQL query
        // dd($query->toSql());    

        // Get filtered records count
        $filteredRecords = $query->count();
        
        $formatuserdata=[];
        foreach ($filteredData as $key => $data) {
            $formatuserdata[$key]['id'] = $data->id;
            $formatuserdata[$key]['customer_number'] = $data->customer_number;
            $formatuserdata[$key]['customer_name'] = $data->customer_name;
            $formatuserdata[$key]['supplier_name'] = $data->supplier_name;
            $formatuserdata[$key]['amount'] = $data->amount;
            $formatuserdata[$key]['date'] = date_format(date_create($data->date), 'm/d/Y');
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
