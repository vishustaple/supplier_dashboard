<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $table = 'accounts_one';

     /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    // 'qbr',
    // 'sf_cat',
    // 'comm_rate',
    // 'parent_id',
    // 'spend_name',
    // 'created_by',
    // 'record_type',
    // 'rebate_freq',
    // 'customer_name',
    // 'member_rebate',
    // 'management_fee',
    // 'record_type_id',
    // 'customer_number',
    // 'supplier_acct_rep',
    // 'category_supplier',
    // 'internal_reporting_name',
    // 'cpg_sales_representative',
    // 'cpg_customer_service_rep',
    
    protected $fillable = [
        'qbr',
        'alies',
        'sf_cat',
        'comm_rate',
        'parent_id',
        'spend_name',
        'created_at',
        'created_by',
        'updated_at',
        'rebate_freq',
        'record_type',
        'account_name',
        'member_rebate',
        'temp_end_date',
        'volume_rebate',
        'management_fee',
        'customer_number',
        'temp_active_date',
        'category_supplier',
        'supplier_acct_rep',
        'sales_representative',
        'internal_reporting_name',
        'cpg_sales_representative',
        'cpg_customer_service_rep',
        'customer_service_representative',
    ];
    
    public function parent(){
        return $this->belongsTo(Account::class, 'parent_id');
    }
    public function grandparent()
    {
        return $this->parent()->with('parent');
    }

    public static function getHierarchy()
    {
        return self::with(['parent' => function ($query) {
            $query->with('parent');
        }])
            ->whereNotNull('parent_id')
            ->get();
    }

    public static function getFilterdAccountsData($filter = [], $csv=false){
        $orderColumnArray = [
            0 => 'id',
            1 => 'customer_number',
            2 => "alies",
            3 => 'category_supplier',
            4 => 'account_name',
            5 => 'record_type',
            6 => 'created_at',
        ];
    
        $query = self::query() // Replace YourModel with the actual model you are using for the data
        // ->leftJoin('suppliers', 'orders.supplier_id', '=', 'suppliers.id')
        // ->leftJoin('accounts', 'orders.customer_number', '=', 'accounts.customer_number')

        ->select('id', 'record_type as record_type', 'created_at as date', 'category_supplier as supplier_name', 'customer_number as customer_number', "alies as customer_name", 'account_name'); // Adjust the column names as needed

        // Filter data based on request parameters
        // if (isset($filter['start_date']) && !empty($filter['start_date']) && isset($filter['end_date']) && !empty($filter['end_date'])) {
        //     $startDate = date_format(date_create($filter['start_date']), 'Y-m-d H:i:s');
        //     $endDate = date_format(date_create($filter['end_date']), 'Y-m-d H:i:s');
        //     // Debug output
        //     // dd('Start Date: ' . $startDate, 'End Date: ' . $endDate);
        //     $query->whereBetween('orders.date', [$startDate, $endDate]);
        //     // dd($query->toSql());    
        // }

        // if (isset($filter['supplierId']) && !empty($filter['supplierId'])) {
        //     $query->where('orders.supplier_id', $filter['supplierId']);
        // }

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
            $formatuserdata[$key]['catagory_supplier'] = $data->catagory_supplier;
            $formatuserdata[$key]['account_name'] = $data->account_name;
            $formatuserdata[$key]['record_type'] = $data->record_type;
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
