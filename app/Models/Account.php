<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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
        'parent_id',
        'parent_name',
        'record_type',
        'account_name',
        'volume_rebate',
        'customer_name',
        'temp_end_date',
        'member_rebate',
        'account_number',
        'grandparent_id',
        'grandparent_name',
        'temp_active_date',
        'category_supplier',
        'cpg_customer_service_rep',
        'cpg_sales_representative',
    ];
    
    public function parent(){
        return $this->belongsTo(Account::class, 'parent_id');
    }
    
    public function grandparent(){
        return $this->parent()->with('parent');
    }

    public static function getHierarchy(){
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
            5 => 'master_account_detail.parent_id',
            6 => 'master_account_detail.grandparent_name',
            7 => 'master_account_detail.grandparent_id',
            8 => 'master_account_detail.record_type',
            9 => 'master_account_detail.id',
        ];

        if ($csv) {
            $query = self::query() /** Eager load relationships */
            ->select('master_account_detail.id as id',
            'master_account_detail.account_number as customer_number',
            'master_account_detail.grandparent_id as grandparent_id',
            'master_account_detail.parent_id as parent_id',
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
             'master_account_detail.grandparent_id as grandparent_id',
             'master_account_detail.parent_id as parent_id',
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
                $formatuserdata[$key]['parent_id'] = $data->parent_id;
                $formatuserdata[$key]['account_name'] = $data->account_name;
                $formatuserdata[$key]['supplier_name'] = $data->supplier_name;
                $formatuserdata[$key]['customer_name'] = $data->customer_name;
                $formatuserdata[$key]['customer_number'] = $data->customer_number;
                // $formatuserdata[$key]['grand_parent_name'] = $data->grand_parent_name;
                // $formatuserdata[$key]['grand_parent_id'] = $data->grandparent_id;
                $formatuserdata[$key]['date'] = date_format(date_create($data->date), 'm/d/Y');
                // $formatuserdata[$key]['id'] = '<div class="dropdown custom_drop_down"><a class="dots" href="#" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa-solid fa-ellipsis-vertical"></i></a> <div class="dropdown-menu"><a class=" " title="View Details" href= '.route('account', ['id' => $data->id]).'><i class="fa-regular  fa-eye"></i>View</a> <a title="Edit Account" class=" " href= '.route('account.edit', ['id' => $data->id,'routename' => 'account']).' ><i class="fa-regular fa-pen-to-square"></i>Edit</a><a hrefe="#" data-id="'. $data->id .'" class="remove" title="Remove Account"><i class="fa-solid fa-trash"></i>Remove</a></div></div>';
                $formatuserdata[$key]['id'] = '<div class="dropdown custom_drop_down"><a class="dots" href="#" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa-solid fa-ellipsis-vertical"></i></a> <div class="dropdown-menu"><a class=" " title="View Details" href= '.route('account', ['id' => $data->id]).'><i class="fa-regular  fa-eye"></i>View</a><a title="Edit Account" class="" id="edit_account" data-id="'.$data->id.'" data-name="'.$data->account_name.'" href="#" data-bs-toggle="modal" data-bs-target="#editAccountModal"><i class="fa-regular fa-pen-to-square"></i>Edit
              </a></div></div>';
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

    public static function getSearchCustomerData($search=''){
        if (!empty($search)) {
            $query = self::query()->select('master_account_detail.account_name as account_name', 'master_account_detail.account_number as customer_number');
            $query->groupBy('master_account_detail.account_name');
            $query->where('master_account_detail.account_name', 'LIKE', '%' . $search . '%');
            $results = $query->get();

            if ($results->isNotEmpty()) {
                foreach ($results as $value) {
                    $finalArray[] = ['id' => $value->account_name, 'text' => $value->account_name];        
                }
                return $finalArray;
            }

            return [];
        }
    }

    public static function getSearchSupplierDatas($search=[]){
        if (!empty($search)) {
            $query = self::query()->select('suppliers.supplier_name as supplier_name', 'master_account_detail.category_supplier as id')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'master_account_detail.category_supplier')
            ->where('master_account_detail.account_name', $search['account_name']);
            $results = $query->first();

            if ($results !== null) {
                $finalArray[] = ['supplier' => $results->supplier_name, 'id' => $results->id];
                return $finalArray;
            }
            return [];
        }
    }

    public static function getSearchAccountData($search=[]){
        if (!empty($search)) {
            $query = self::query()->select('master_account_detail.account_number as account')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'master_account_detail.category_supplier')
            ->where('master_account_detail.account_number', 'LIKE', '%' . $search['q'] . '%')
            ->where('master_account_detail.account_number', $search['customer_number']);
            $results = $query->get();

            if ($results->isNotEmpty()) {
                foreach ($results as $value) {
                    $finalArray[] = ['id' => $value->account, 'text' => $value->account];        
                }

                return $finalArray;
            }

            return [];
        }
    }

    public static function getFilterdUpdateRebateData($filter = [], $csv=false){
        $orderColumnArray = [
            0 => 'master_account_detail.account_number',
            1 => 'master_account_detail.customer_name',
            2 => 'master_account_detail.account_name',
            3 => 'master_account_detail.category_supplier',
            4 => 'master_account_detail.parent_name',
            5 => 'master_account_detail.grandparent_name',
            6 => 'master_account_detail.record_type',
            7 => 'master_account_detail.id',
        ];
      
        $query = self::query() /** Eager load relationships */
        ->select(
            'rebate.volume_rebate as volume_rebate',
            'rebate.incentive_rebate as incentive_rebate',
            'master_account_detail.id as id',
            'master_account_detail.record_type as record_type',
            'master_account_detail.created_at as date',
            'suppliers.supplier_name as supplier_name',
            'master_account_detail.account_number as account_number',
            'master_account_detail.customer_name as customer_name',
            'master_account_detail.account_name as account_name',
            'master_account_detail.category_supplier as supplier_id'
        )

        ->leftJoin('suppliers', 'suppliers.id', '=', 'master_account_detail.category_supplier')
        ->leftJoin('rebate', 'master_account_detail.account_name', '=', 'rebate.account_name')

        ->whereNotNull('master_account_detail.account_name')
        ->where('master_account_detail.account_name', '!=', '');

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

        $query->whereNull('rebate.volume_rebate');

        /** Get total records count (without filtering) */
        $query->groupBy('master_account_detail.account_name');
        $query->groupBy('suppliers.supplier_name');

        $totalRecords = $query->getQuery()->getCountForPagination();

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
        
        $formatuserdata=[];
        foreach ($filteredData as $key => $data) {
            $formatuserdata[$key]['customer_name'] = $data->customer_name;
            $formatuserdata[$key]['account_number'] = $data->account_number;
            $formatuserdata[$key]['account_name'] = $data->account_name;
            $formatuserdata[$key]['supplier_name'] = $data->supplier_name;
            $formatuserdata[$key]['volume_rebate'] = "<form action='' method='post'><input type='text' class='form-control form-control-sm volume_rebate' name='volume_rebate[]' value='".$data->volume_rebate."' required/>" ;

            if ($data->supplier_id == 3) {
                $formatuserdata[$key]['incentive_rebate'] = "<input type='hidden' value='".$data->account_name."' class='account_name'><input type='text' class='form-control form-control-sm incentive_rebate' name='incentive_rebate[]' value='".$data->incentive_rebate."'  required/>";
            } else {
                $formatuserdata[$key]['incentive_rebate'] = "<input type='hidden' value='".$data->account_name."' class='account_name'><input type='text' class='form-control form-control-sm incentive_rebate' name='incentive_rebate[]' disabled value='0'  required/>";
            }
            
            $formatuserdata[$key]['id'] = '<button type="button" class="save_rebate btn btn-success"> Save </button></form>';
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

    public static function getFilterdRebateData($filter = [], $csv=false){
        $orderColumnArray = [
            0 => 'master_account_detail.account_number',
            1 => 'master_account_detail.customer_name',
            2 => 'master_account_detail.account_name',
            3 => 'suppliers.supplier_name',
        ];

        $query = self::query() /** Eager load relationships */
        ->select(
            'rebate.volume_rebate as volume_rebate',
            'rebate.incentive_rebate as incentive_rebate',
            'master_account_detail.id as id',
            'suppliers.supplier_name as supplier_name',
            'master_account_detail.category_supplier as supplier_id',
            'master_account_detail.account_number as account_number',
            'master_account_detail.customer_name as customer_name',
            'master_account_detail.account_name as account_name'
        )

        ->leftJoin('suppliers', 'suppliers.id', '=', 'master_account_detail.category_supplier')
        ->leftJoin('rebate', 'master_account_detail.account_name', '=', 'rebate.account_name')

        ->whereNotNull('master_account_detail.account_name')
        ->where('master_account_detail.account_name', '!=', '');
        $query->whereNotNull('rebate.volume_rebate')->whereNotNull('rebate.incentive_rebate');
         
        /** Search functionality */
        if (isset($filter['search']['value']) && !empty($filter['search']['value'])) {
            $searchTerm = $filter['search']['value'];

            $query->where(function ($q) use ($searchTerm, $orderColumnArray) {
                foreach ($orderColumnArray as $column) {
                    $q->orWhere($column, 'LIKE', '%' . $searchTerm . '%');
                }
            });            
        }
       
        /** Get total records count (without filtering) */
        $query->groupBy('master_account_detail.account_name');
        $query->groupBy('suppliers.supplier_name');
        $totalRecords = $query->getQuery()->getCountForPagination();

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
        
        $formatuserdata=[];
        foreach ($filteredData as $key => $data) {
            $formatuserdata[$key]['customer_name'] = $data->customer_name;
            $formatuserdata[$key]['account_number'] = $data->account_number;
            $formatuserdata[$key]['account_name'] = $data->account_name;
            $formatuserdata[$key]['supplier_name'] = $data->supplier_name;
            $formatuserdata[$key]['volume_rebate'] = "<form action='' method='post'><input type='text' class='form-control form-control-sm volume_rebate' name='volume_rebate[]' value='".$data->volume_rebate."' required/>" ;

            if ($data->supplier_id == 3) {
                $formatuserdata[$key]['incentive_rebate'] = "<input type='hidden' value='".$data->account_name."' class='account_name'><input type='text' class='form-control form-control-sm incentive_rebate' name='incentive_rebate[]' value='".$data->incentive_rebate."'  required/>";
            } else {
                $formatuserdata[$key]['incentive_rebate'] = "<input type='hidden' value='".$data->account_name."' class='account_name'><input type='text' class='form-control form-control-sm incentive_rebate' disabled name='incentive_rebate[]' value='0' required/>";
            }

            $formatuserdata[$key]['id'] = '<button type="button" class="save_rebate btn btn-success"> Update </button></form>';
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

    public static function getSearchGPNameData(){
        $query = self::query()->select('grandparent_name')->whereNotNull('grandparent_name');
        $query->groupBy('grandparent_name');
        $results = $query->get();

        if ($results->isNotEmpty()) {
            foreach ($results as $value) {
                $finalArray[] = ['id' => $value->grandparent_name, 'customer_name' => $value->grandparent_name];        
            }

            return $finalArray;
        }
    }

    public static function getSearchGPNumberData(){
        $query = self::query()->select('grandparent_id')->whereNotNull('grandparent_id');
        $query->groupBy('grandparent_id');
        $results = $query->get();

        if ($results->isNotEmpty()) {
            foreach ($results as $value) {
                $finalArray[] = ['id' => $value->grandparent_id, 'customer_name' => $value->grandparent_id];        
            }

            return $finalArray;
        }
    }

    public static function getSearchPNameData(){
        $query = self::query()->select('parent_name')->whereNotNull('parent_name');
        $query->groupBy('parent_name');
        $results = $query->get();

        if ($results->isNotEmpty()) {
            foreach ($results as $value) {
                $finalArray[] = ['id' => $value->parent_name, 'customer_name' => $value->parent_name];        
            }

            return $finalArray;
        }            
    }

    public static function getSearchPNumberData(){
        $query = self::query()->select('parent_id')->whereNotNull('parent_id');
        $query->groupBy('parent_name');
        $results = $query->get();

        if ($results->isNotEmpty()) {
            foreach ($results as $value) {
                $finalArray[] = ['id' => $value->parent_id, 'customer_name' => $value->parent_id];        
            }

            return $finalArray;
        }            
    }
}
