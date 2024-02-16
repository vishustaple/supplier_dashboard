<?php

namespace App\Models;

use DB;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Account extends Model
{
    use HasFactory;

    protected $table = 'accounts';

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

        if ($csv) {
            $query = self::with('parent.parent') // Eager load relationships
            ->select('accounts.id as id',
            'accounts.customer_number as customer_number',
            'accounts.alies as customer_name',
            'accounts.account_name as account_name',
            'accounts.volume_rebate as volume_rebate',
            'accounts.sales_representative as sales_representative',
            'accounts.customer_service_representative as customer_service_representative',
            'accounts.member_rebate as member_rebate',
            'accounts.temp_active_date as temp_active_date',
            'accounts.temp_end_date as temp_end_date',
            'accounts.internal_reporting_name as internal_reporting_name',
            'accounts.qbr as qbr',
            'accounts.spend_name as spend_name',
            'accounts.supplier_acct_rep as supplier_acct_rep',
            'accounts.management_fee as management_fee',
            'accounts.record_type as record_type',
            'accounts.cpg_sales_representative as cpg_sales_representative',
            'accounts.cpg_customer_service_rep as cpg_customer_service_rep',
            'accounts.sf_cat as sf_cat',
            'accounts.rebate_freq as rebate_freq',
            'accounts.comm_rate as comm_rate',
            'suppliers.supplier_name as category_supplier',
            DB::raw("parent.alies as parent_name"),
            DB::raw("grandparent.alies as grand_parent_name"))
            ->leftJoin('accounts as parent', 'parent.id', '=', 'accounts.parent_id')
            ->leftJoin('accounts as grandparent', 'grandparent.id', '=', 'parent.parent_id')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'accounts.category_supplier');
        } else {
            $query = self::with('parent.parent') // Eager load relationships
            ->select('accounts.id as id', 'accounts.record_type as record_type', 'accounts.created_at as date', 'suppliers.supplier_name as supplier_name', 'accounts.customer_number as customer_number', "accounts.alies as customer_name", 'accounts.account_name as account_name',
            DB::raw("parent.alies as parent_name"),
            DB::raw("grandparent.alies as grand_parent_name"))
            ->leftJoin('accounts as parent', 'parent.id', '=', 'accounts.parent_id')
            ->leftJoin('accounts as grandparent', 'grandparent.id', '=', 'parent.parent_id')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'accounts.category_supplier');
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
        if (isset($filter['order'][0]['column']) && isset($orderColumnArray[$filter['order'][0]['column']]) && isset($filter['order'][0]['dir'])) {
            // Order by column and direction
            $query->orderBy($orderColumnArray[$filter['order'][0]['column']], $filter['order'][0]['dir']);
        } else {
            $query->orderBy($orderColumnArray[0], 'asc');
        }

        if (isset($filter['start']) && isset($filter['length'])) {
            // Get paginated results based on start, length
            $filteredData = $query->skip($filter['start'])->take($filter['length'])->get();
        } else {
            $filteredData = $query->get();
        }
        // Print the SQL query
        // dd($query->toSql());    

        // Get filtered records count
        $filteredRecords = $query->count();
        
        $formatuserdata=[];
        foreach ($filteredData as $key => $data) {
            if($csv){
                $formatuserdata[] = [
                    'customer_number' => isset($data->customer_number) ? "\t" .$data->customer_number : null,
                    'customer_name' => $data->customer_name,
                    'account_name' => $data->account_name,
                    'grand_parent_name' => $data->grand_parent_name,
                    'parent_name' => $data->parent_name,
                    'volume_rebate' => $data->volume_rebate,
                    'sales_representative' => $data->sales_representative,
                    'customer_service_representative' => $data->customer_service_representative,
                    'member_rebate' => $data->member_rebate,
                    'temp_active_date' => isset($data->temp_active_date) && !empty($data->temp_active_date) ? Carbon::parse($data->temp_active_date)->format('Y-m-d') : null ,
                    'temp_end_date' => isset($data->temp_end_date) && !empty($data->temp_end_date) ? Carbon::parse($data->temp_end_date)->format('Y-m-d') : null ,
                    'internal_reporting_name' => $data->internal_reporting_name,
                    'qbr' => $data->qbr,
                    'spend_name' => $data->spend_name,
                    'supplier_acct_rep' => $data->supplier_acct_rep,
                    'management_fee' => $data->management_fee,
                    'category' => $data->record_type,
                    'supplier' => $data->category_supplier,
                    'cpg_sales_representative' => $data->cpg_sales_representative,
                    'cpg_customer_service_rep' => $data->cpg_customer_service_rep,
                    'sf_cat' => $data->sf_cat,
                    'rebate_freq' => $data->rebate_freq,
                    'comm_rate' => $data->comm_rate,
            ];
            } else {
                $formatuserdata[$key]['record_type'] = $data->record_type;
                $formatuserdata[$key]['parent_name'] = $data->parent_name;
                $formatuserdata[$key]['account_name'] = $data->account_name;
                $formatuserdata[$key]['supplier_name'] = $data->supplier_name;
                $formatuserdata[$key]['customer_name'] = $data->customer_name;
                $formatuserdata[$key]['customer_number'] = $data->customer_number;
                $formatuserdata[$key]['grand_parent_name'] = $data->grand_parent_name;
                $formatuserdata[$key]['date'] = date_format(date_create($data->date), 'm/d/Y');
                $formatuserdata[$key]['id'] = '<div class="dropdown custom_drop_down"><a class="dots" href="#" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa-solid fa-ellipsis-vertical"></i></a> <div class="dropdown-menu"><a class=" " title="View Details" href= '.route('account', ['id' => $data->id]).'><i class="fa-regular  fa-eye"></i>View</a> <a title="Edit Account" class=" " href= '.route('account.edit', ['id' => $data->id,'routename' => 'account']).' ><i class="fa-regular fa-pen-to-square"></i>Edit</a><a hrefe="#" data-id="'. $data->id .'" class="remove" title="Remove Account"><i class="fa-solid fa-trash"></i>Remove</a></div></div>';
            }
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
