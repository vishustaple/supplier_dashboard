<?php

namespace App\Models;

use DB;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Account extends Model
{
    use HasFactory;

    protected $table = 'master_account_detail';

     /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    
    protected $fillable = [
        // 'qbr',
        // 'alies',
        // 'sf_cat',
        // 'comm_rate',
        // 'parent_id',
        // 'spend_name',
        // 'created_at',
        // 'created_by',
        // 'updated_at',
        // 'rebate_freq',
        // 'record_type',
        // 'account_name',
        // 'member_rebate',
        // 'temp_end_date',
        // 'volume_rebate',
        // 'management_fee',
        // 'customer_number',
        // 'temp_active_date',
        // 'category_supplier',
        // 'supplier_acct_rep',
        // 'sales_representative',
        // 'internal_reporting_name',
        // 'cpg_sales_representative',
        // 'cpg_customer_service_rep',
        // 'customer_service_representative',
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
            0 => 'master_account_detail.account_number',
            1 => "master_account_detail.customer_name",
            2 => 'master_account_detail.account_name',
            3 => 'master_account_detail.category_supplier',
            4 => 'master_account_detail.parent_name',
            5 => 'master_account_detail.grandparent_name',
            6 => 'master_account_detail.record_type',
            7 => 'master_account_detail.id',
        ];

        if ($csv) {
            $query = self::with('parent.parent') /** Eager load relationships */
            ->select('master_account_detail.id as id',
            'master_account_detail.account_number as customer_number',
            'master_account_detail.customer_name as customer_name',
            'master_account_detail.account_name as account_name',
            'master_account_detail.volume_rebate as volume_rebate',
            'master_account_detail.member_rebate as member_rebate',
            'master_account_detail.temp_active_date as temp_active_date',
            'master_account_detail.temp_end_date as temp_end_date',
            'master_account_detail.record_type as record_type',
            'master_account_detail.cpg_sales_representative as cpg_sales_representative',
            'master_account_detail.cpg_customer_service_rep as cpg_customer_service_rep',
            'suppliers.supplier_name as category_supplier',
            'master_account_detail.parent_name as parent_name',
            'master_account_detail.grandparent_name as grand_parent_name')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'master_account_detail.category_supplier');
        } else {
            $query = self::query() /** Eager load relationships */
            ->select('master_account_detail.parent_name as parent_name',
             'master_account_detail.grandparent_name as grand_parent_name',
             'master_account_detail.id as id',
             'master_account_detail.record_type as record_type',
             'master_account_detail.created_at as date',
             'suppliers.supplier_name as supplier_name',
             'master_account_detail.account_number as customer_number',
             'master_account_detail.customer_name as customer_name',
             'master_account_detail.account_name as account_name')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'master_account_detail.category_supplier');
        }
        /** Search functionality */
        if (isset($filter['search']['value']) && !empty($filter['search']['value'])) {
            $searchTerm = $filter['search']['value'];

            $query->where(function ($q) use ($searchTerm, $orderColumnArray) {
                foreach ($orderColumnArray as $column) {
                    $q->orWhere($column, 'LIKE', '%' . $searchTerm . '%');
                }
            });
            
            $query->orWhere('suppliers.supplier_name', 'LIKE', '%' . $searchTerm . '%');
        }

        /** Get total records count (without filtering) */
        $totalRecords = $query->count();
        if (isset($filter['order'][0]['column']) && isset($orderColumnArray[$filter['order'][0]['column']]) && isset($filter['order'][0]['dir'])) {
            /** Order by column and direction */
            $query->orderBy($orderColumnArray[$filter['order'][0]['column']], $filter['order'][0]['dir']);
        } else {
            $query->orderBy($orderColumnArray[0], 'asc');
        }

        if (isset($filter['start']) && isset($filter['length'])) {
            /** Get paginated results based on start, length */
            $filteredData = $query->skip($filter['start'])->take($filter['length'])->get();
        } else {
            $filteredData = $query->get();
        }
        /** Print the SQL query */
        // dd($query->toSql());    

        /** Get filtered records count */
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
            /** Return the result along with total and filtered counts */
            return [
                'data' => $formatuserdata,
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $totalRecords,
            ];
        }
    }
}
