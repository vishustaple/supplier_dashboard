<?php

namespace App\Models;

use Illuminate\Support\{Carbon, Facades\DB};
use Illuminate\Database\Eloquent\{Model, Factories\HasFactory};

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
        'supplier_id',
        'customer_id',
        'account_name',
        'volume_rebate',
        'temp_end_date',
        'member_rebate',
        'account_number',
        'grandparent_id',
        'grandparent_name',
        'temp_active_date',
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
            1 => "customers.customer_name",
            2 => 'master_account_detail.account_name',
            3 => 'master_account_detail.supplier_id',
            4 => 'master_account_detail.parent_name',
            5 => 'master_account_detail.parent_id',
            6 => 'master_account_detail.grandparent_name',
            7 => 'master_account_detail.grandparent_id',
            8 => 'master_account_detail.record_type',
            9 => 'master_account_detail.id',
        ];

        if ($csv) {
            $query = self::query() /** Eager load relationships */
            ->select(
                'customers.id as customer_id',
                'master_account_detail.id as id',
                'suppliers.supplier_name as supplier_id',
                'customers.customer_name as customer_name',
                'master_account_detail.parent_id as parent_id',
                'master_account_detail.parent_name as parent_name',
                'master_account_detail.record_type as record_type',
                'master_account_detail.account_name as account_name',
                'master_account_detail.member_rebate as member_rebate',
                'master_account_detail.volume_rebate as volume_rebate',
                'master_account_detail.temp_end_date as temp_end_date',
                'master_account_detail.grandparent_id as grandparent_id',
                'master_account_detail.account_number as customer_number',
                'master_account_detail.temp_active_date as temp_active_date',
                'master_account_detail.grandparent_name as grand_parent_name',
                'master_account_detail.cpg_sales_representative as cpg_sales_representative',
                'master_account_detail.cpg_customer_service_rep as cpg_customer_service_rep',
            );
        } else {
            $query = self::query() /** Eager load relationships */
            ->select(
                'customers.id as customer_id',
                'master_account_detail.id as id',
                'suppliers.supplier_name as supplier_name',
                'customers.customer_name as customer_name',
                'master_account_detail.created_at as date',
                'master_account_detail.parent_id as parent_id',
                'master_account_detail.parent_name as parent_name',
                'master_account_detail.record_type as record_type',
                'master_account_detail.account_name as account_name',
                'master_account_detail.grandparent_id as grandparent_id',
                'master_account_detail.account_number as customer_number',
                'master_account_detail.grandparent_name as grand_parent_name',
            );
        }

        $query->leftJoin('suppliers', 'suppliers.id', '=', 'master_account_detail.supplier_id')
        ->leftJoin('customers', 'customers.id', '=', 'master_account_detail.customer_id');

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

        /** Group by with account name */
        $query->groupBy('master_account_detail.account_name', 'master_account_detail.supplier_id');

        /** Get total records count (without filtering) */
        $totalRecords = $query->getQuery()->getCountForPagination();
        
        /** Order by column and direction */
        if (isset($filter['order'][0]['column']) && isset($orderColumnArray[$filter['order'][0]['column']]) && isset($filter['order'][0]['dir'])) {
            $query->orderBy($orderColumnArray[$filter['order'][0]['column']], $filter['order'][0]['dir']);
        } else {
            $query->orderBy($orderColumnArray[0], 'asc');
        }

        /** Get paginated results based on start, length */
        if (isset($filter['start']) && isset($filter['length'])) {
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
                    'supplier' => $data->supplier_id,
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
                $formatuserdata[$key]['date'] = date_format(date_create($data->date), 'm/d/Y');
                $formatuserdata[$key]['id'] = '<div class="dropdown custom_drop_down"><a class="dots" href="#" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa-solid fa-ellipsis-vertical"></i></a> <div class="dropdown-menu"><a class=" " title="View Details" href= '.route('account', ['id' => $data->id]).'><i class="fa-regular  fa-eye"></i>View</a><a title="Edit Account" class="" id="edit_account" data-id="'.$data->id.'" data-name="'.$data->account_name.'" data-parent_name="'.$data->parent_name.'" data-parent_number="'.$data->parent_id.'"data-customer_id="'.$data->customer_id.'" data-customer_name="'.$data->customer_name.'" data-category_name="'.$data->record_type.'" href="#" data-bs-toggle="modal" data-bs-target="#editAccountModal"><i class="fa-regular fa-pen-to-square"></i>Edit
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

    public static function getFilterdAccountsAllData($filter = [], $csv=false){
        $orderColumnArray = [
            0 => 'master_account_detail.account_number',
            1 => "customers.customer_name",
            2 => 'master_account_detail.account_name',
            3 => 'master_account_detail.parent_id',
            4 => 'master_account_detail.parent_name',
            5 => 'master_account_detail.supplier_id',
            6 => 'master_account_detail.record_type',
            7 => 'master_account_detail.created_at',
        ];

        $query = self::query() /** Eager load relationships */
        ->select(
            'customers.customer_name as customer_name',
            'suppliers.supplier_name as supplier_name',
            'master_account_detail.parent_id as parent_id',
            'master_account_detail.parent_name as parent_name',
            'master_account_detail.record_type as record_type',
            'master_account_detail.account_name as account_name',
            'master_account_detail.account_number as customer_number',
            DB::raw("DATE_FORMAT(master_account_detail.created_at, '%m/%d/%Y') as date"),
        )
        ->leftJoin('customers', 'customers.id', '=', 'master_account_detail.customer_id')
        ->leftJoin('suppliers', 'suppliers.id', '=', 'master_account_detail.supplier_id');

        /** Group by with account name */
        if (isset($filter['account_name']) && !empty($filter['account_name'])) {
            $query->where('master_account_detail.account_name', $filter['account_name']);
        }

        /** Get total records count (without filtering) */
        $totalRecords = $query->getQuery()->getCountForPagination();

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

        /** Order by column and direction */
        if (isset($filter['order'][0]['column']) && isset($orderColumnArray[$filter['order'][0]['column']]) && isset($filter['order'][0]['dir'])) {
            $query->orderBy($orderColumnArray[$filter['order'][0]['column']], $filter['order'][0]['dir']);
        } else {
            $query->orderBy($orderColumnArray[0], 'asc');
        }

        /** Get filtered records count */
        $filteredRecords = $query->getQuery()->getCountForPagination();

        /** Get paginated results based on start, length */
        if (isset($filter['start']) && isset($filter['length'])) {
            $filteredData = $query->skip($filter['start'])->take($filter['length'])->get();
        } else {
            $filteredData = $query->get();
        }
        
        /** Print the SQL query */
        // dd($query->toSql());    

        return [
            'data' => $filteredData,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
        ];
    }

    public static function getSearchCustomerData($search='', $supplier='', $supplierArray=[], $check=false, $allSupplier=false){
        if (!empty($search)) {
            $query = self::query()
            ->select(
                'master_account_detail.account_name as account_name',
                'master_account_detail.account_number as customer_number'
            );
            $query->groupBy('master_account_detail.account_name');

            if ($check == true) {
                if (!empty($supplier)) {
                    $query->where('master_account_detail.supplier_id', $supplier);
                } else if (!empty($supplierArray)) {
                    if ($supplierArray[0] == 'all') {
                        $query->whereIn('master_account_detail.supplier_id', [1, 2, 3, 4, 5, 6, 7]);
                    } else {
                        $query->whereIn('master_account_detail.supplier_id', $supplierArray);
                    }
                } else if ($allSupplier) {
                    $query->whereIn('master_account_detail.supplier_id', [1, 2, 3, 4, 5, 6, 7]);
                } else {
                    return [];
                }
            }

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
            $query = self::query()
            ->select(
                'suppliers.supplier_name as supplier_name',
                'master_account_detail.supplier_id as id'
            )
            ->leftJoin('suppliers', 'suppliers.id', '=', 'master_account_detail.supplier_id')
            ->where('master_account_detail.account_name', 'LIKE', '%' . $search['account_name'] . '%');

            if (isset($search['check']) && $search['check'] == "true") {
                $query->whereIn('master_account_detail.supplier_id', [1, 2, 3, 4, 5]);
                $query->groupBy('master_account_detail.supplier_id');
                $results = $query->get();
            } else {
                $results = $query->first();
            }

            $finalArray = [];
            if ($results !== null) {
                if (isset($search['check']) && $search['check'] == "true") {
                    foreach ($results as $value) {
                        $finalArray[] = ['supplier' => $value->supplier_name, 'id' => $value->id];
                    }
                } else {
                    $finalArray[] = ['supplier' => $results->supplier_name, 'id' => $results->id];
                }
                return $finalArray;
            }
            return [];
        }
    }

    public static function getSearchAccountData($search=[]){
        if (!empty($search)) {
            $query = self::query()
            ->select('master_account_detail.account_number as account')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'master_account_detail.supplier_id')
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
            1 => 'customers.customer_name',
            2 => 'master_account_detail.account_name',
            3 => 'master_account_detail.supplier_id',
            4 => 'master_account_detail.parent_name',
            5 => 'master_account_detail.grandparent_name',
            6 => 'master_account_detail.record_type',
            7 => 'master_account_detail.id',
        ];
      
        $query = self::query() /** Eager load relationships */
        ->select(
            'master_account_detail.id as id',
            'rebate.volume_rebate as volume_rebate',
            'customers.customer_name as customer_name',
            'suppliers.supplier_name as supplier_name',
            'master_account_detail.created_at as date',
            'rebate.incentive_rebate as incentive_rebate',
            'master_account_detail.record_type as record_type',
            'master_account_detail.account_name as account_name',
            'master_account_detail.account_number as account_number',
            'master_account_detail.supplier_id as supplier_id',
        )

        ->leftJoin('customers', 'master_account_detail.customer_id', '=', 'customers.id')
        ->leftJoin('suppliers', 'suppliers.id', '=', 'master_account_detail.supplier_id')
        ->leftJoin('rebate', function($join) {
            $join->on('master_account_detail.account_name', '=', 'rebate.account_name')
            ->on('master_account_detail.supplier_id', '=', 'rebate.supplier');
        })

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

        /** Order by column and direction */
        if (isset($filter['order'][0]['column']) && isset($orderColumnArray[$filter['order'][0]['column']]) && isset($filter['order'][0]['dir'])) {
            $query->orderBy($orderColumnArray[$filter['order'][0]['column']], $filter['order'][0]['dir']);
        } else {
            $query->orderBy($orderColumnArray[0], 'asc');
        }

        /** Get paginated results based on start, length */
        if (isset($filter['start']) && isset($filter['length'])) {
            $filteredData = $query->skip($filter['start'])->take($filter['length'])->get();
        } else {
            $filteredData = $query->get();
        }

        /** Print the SQL query */
        // dd($query->toSql());
        
        $formatuserdata=[];
        foreach ($filteredData as $key => $data) {
            $formatuserdata[$key]['account_name'] = $data->account_name;
            $formatuserdata[$key]['customer_name'] = $data->customer_name;
            $formatuserdata[$key]['supplier_name'] = $data->supplier_name;
            $formatuserdata[$key]['account_number'] = $data->account_number;
            $formatuserdata[$key]['volume_rebate'] = "<form action='' method='post'><input type='text' class='form-control form-control-sm volume_rebate' name='volume_rebate[]' value='".$data->volume_rebate."' required/>" ;
           
            if ($data->supplier_id == 3) {
                $formatuserdata[$key]['incentive_rebate'] = "<input type='hidden' value='".htmlspecialchars($data->account_name, ENT_QUOTES)."' class='account_name'><input type='hidden' value='".$data->supplier_id."' class='supplier_id'><input type='text' class='form-control form-control-sm incentive_rebate' name='incentive_rebate[]' value='".$data->incentive_rebate."'  required/>";
            } else {
                $formatuserdata[$key]['incentive_rebate'] = "<input type='hidden' value='".htmlspecialchars($data->account_name, ENT_QUOTES)."' class='account_name'><input type='hidden' value='".$data->supplier_id."' class='supplier_id'><input type='text' class='form-control form-control-sm incentive_rebate' name='incentive_rebate[]' disabled value='0'  required/>";
            }
            
            $formatuserdata[$key]['id'] = '<button type="button" class="save_rebate btn btn-success"> Save </button></form>';
        }

        /** Return the result along with total and filtered counts */
        if ($csv == true) {
            return $formatuserdata;
        } else {
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
            1 => 'customers.customer_name',
            2 => 'master_account_detail.account_name',
            3 => 'suppliers.supplier_name',
        ];

        $query = self::query() /** Eager load relationships */
        ->select(
            'master_account_detail.id as id',
            'rebate.volume_rebate as volume_rebate',
            'customers.customer_name as customer_name',
            'suppliers.supplier_name as supplier_name',
            'rebate.incentive_rebate as incentive_rebate',
            'master_account_detail.account_name as account_name',
            'master_account_detail.supplier_id as supplier_id',
            'master_account_detail.account_number as account_number',
        )

        ->leftJoin('suppliers', 'suppliers.id', '=', 'master_account_detail.supplier_id')
        ->leftJoin('customers', 'master_account_detail.customer_id', '=', 'customers.id')
        ->leftJoin('rebate', function($join) {
            $join->on('master_account_detail.account_name', '=', 'rebate.account_name')
            ->on('master_account_detail.supplier_id', '=', 'rebate.supplier');
        })

        ->whereNotNull('master_account_detail.account_name')
        ->where('master_account_detail.account_name', '!=', '');
        $query->whereNotNull('rebate.volume_rebate');
         
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
        $query->groupBy('master_account_detail.account_name');
        $query->groupBy('suppliers.supplier_name');
        $totalRecords = $query->getQuery()->getCountForPagination();

        /** Order by column and direction */
        if (isset($filter['order'][0]['column']) && isset($orderColumnArray[$filter['order'][0]['column']]) && isset($filter['order'][0]['dir'])) {
            $query->orderBy($orderColumnArray[$filter['order'][0]['column']], $filter['order'][0]['dir']);
        } else {
            $query->orderBy($orderColumnArray[0], 'asc');
        }

        /** Get paginated results based on start, length */
        if (isset($filter['start']) && isset($filter['length'])) {
            $filteredData = $query->skip($filter['start'])->take($filter['length'])->get();
        } else {
            $filteredData = $query->get();
        }

        /** Print the SQL query */
        // dd($query->toSql());
        
        $formatuserdata=[];
        foreach ($filteredData as $key => $data) {
            $formatuserdata[$key]['account_name'] = $data->account_name;
            $formatuserdata[$key]['supplier_name'] = $data->supplier_name;
            $formatuserdata[$key]['customer_name'] = $data->customer_name;
            $formatuserdata[$key]['account_number'] = $data->account_number;
            $formatuserdata[$key]['volume_rebate'] = "<form action='' method='post'><input type='text' class='form-control form-control-sm volume_rebate' name='volume_rebate[]' value='".$data->volume_rebate."' required/>" ;

            if ($data->supplier_id == 3) {
                $formatuserdata[$key]['incentive_rebate'] = "<input type='hidden' value='".$data->supplier_id."' class='supplier_id'><input type='hidden' value='".$data->account_name."' class='account_name'><input type='text' class='form-control form-control-sm incentive_rebate' name='incentive_rebate[]' value='".$data->incentive_rebate."'  required/>";
            } else {
                $formatuserdata[$key]['incentive_rebate'] = "<input type='hidden' value='".$data->supplier_id."' class='supplier_id'><input type='hidden' value='".$data->account_name."' class='account_name'><input type='text' class='form-control form-control-sm incentive_rebate' disabled name='incentive_rebate[]' value='0' required/>";
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
        $results = self::query()
        ->select('grandparent_name')
        ->whereNotNull('grandparent_name')
        ->groupBy('grandparent_name')
        ->get();

        if ($results->isNotEmpty()) {
            foreach ($results as $value) {
                $finalArray[] = [
                    'id' => $value->grandparent_name,
                    'customer_name' => $value->grandparent_name
                ];        
            }

            return $finalArray;
        }
    }

    public static function getSearchGPNumberData(){
        $results = self::query()
        ->select('grandparent_id')
        ->whereNotNull('grandparent_id')
        ->groupBy('grandparent_id')
        ->get();

        if ($results->isNotEmpty()) {
            foreach ($results as $value) {
                $finalArray[] = [
                    'id' => $value->grandparent_id,
                    'customer_name' => $value->grandparent_id
                ];        
            }

            return $finalArray;
        }
    }

    public static function getSearchPNameData(){
        $results = self::query()
        ->select('parent_name')
        ->whereNotNull('parent_name')
        ->groupBy('parent_name')
        ->get();

        if ($results->isNotEmpty()) {
            foreach ($results as $value) {
                $finalArray[] = [
                    'id' => $value->parent_name,
                    'customer_name' => $value->parent_name
                ];        
            }

            return $finalArray;
        }            
    }

    public static function getSearchPNumberData(){
        $results = self::query()
        ->select('parent_id')
        ->whereNotNull('parent_id')
        ->groupBy('parent_name')
        ->get();

        if ($results->isNotEmpty()) {
            foreach ($results as $value) {
                $finalArray[] = [
                    'id' => $value->parent_id,
                    'customer_name' => $value->parent_id
                ];        
            }

            return $finalArray;
        }            
    }
}
