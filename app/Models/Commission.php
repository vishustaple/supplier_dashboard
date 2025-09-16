<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\{Model, Factories\HasFactory};

class Commission extends Model
{
    use HasFactory;

    protected $table = 'commissions';

     /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'status',
        'supplier',
        'end_date',
        'sales_rep',
        'commissions',
        'start_date',
        'account_name',
    ];

    public static function getFilterdCommissionData($filter=[], $csv=false){
        $orderColumnArray = [
            0 => 'commissions.account_name',
            1 => 'suppliers.supplier_name',
            2 => 'commissions.sales_rep',
            3 => 'commissions.commissions',
            4 => 'commissions.start_date',
            5 => 'commissions.end_date',
            6 => 'commissions.status',
            7 => 'commissions.id',
        ];
   
        $query = self::query() /** Replace YourModel with the actual model you are using for the data */
        ->leftJoin('suppliers', 'commissions.supplier', '=', 'suppliers.id')
        ->leftJoin('sales_team', 'sales_team.id', '=', 'commissions.sales_rep')
        ->select(
            'commissions.account_name as account_name',
            'suppliers.supplier_name as supplier_name',
            'commissions.commissions as commissions',
            'commissions.start_date as start_date',
            'commissions.status as status',
            DB::raw("CONCAT(sales_team.first_name, ' ', sales_team.last_name) AS sales_rep"),
            'commissions.end_date as end_date',
            'commissions.id as id'
        ); /** Adjust the column names as needed */
    
        $totalRecords = $query->getQuery()->getCountForPagination();

        /** Search functionality */
        if (isset($filter['search']['value']) && !empty($filter['search']['value'])) {
            $searchTerm = $filter['search']['value'];
            $query->where(function ($q) use ($searchTerm, $orderColumnArray) {
                foreach ($orderColumnArray as $column) {
                    if ($column == 'commissions.sales_rep') {
                        $q->orWhere(DB::raw("CONCAT(sales_team.first_name, ' ', sales_team.last_name)"), 'LIKE', '%' . $searchTerm . '%');
                    } else {
                        $q->orWhere($column, 'LIKE', '%' . $searchTerm . '%');
                    }
                }
            });
        }

        /** Order by column and direction */
        if (isset($filter['order'][0]['column']) && isset($orderColumnArray[$filter['order'][0]['column']]) && isset($filter['order'][0]['dir'])) {
            $query->orderBy($orderColumnArray[$filter['order'][0]['column']], $filter['order'][0]['dir']);
        } else {
            $query->orderBy($orderColumnArray[0], 'asc');
        }

        $filteredRecords = $query->getQuery()->getCountForPagination();

        /** Get paginated results based on start, length */
        if (isset($filter['start']) && isset($filter['length'])) {
            $filteredData = $query->skip($filter['start'])->take($filter['length'])->get();
        } else {
            $filteredData = $query->get();
        }

        /** Print the SQL query */
        // dd($filteredData->toArray());    

        $formatuserdata=[];
        foreach ($filteredData as $key => $data) {
            $formatuserdata[$key]['sales_rep'] = $data->sales_rep;
            $formatuserdata[$key]['account_name'] = $data->account_name;
            $formatuserdata[$key]['supplier_name'] = $data->supplier_name;
            $formatuserdata[$key]['customer_name'] = $data->customer_name;
            $formatuserdata[$key]['customer_number'] = $data->customer_number;
            $formatuserdata[$key]['commissions'] = $data->commissions.'%';
            $formatuserdata[$key]['start_date'] = date('m/d/Y', strtotime($data->start_date));
            $formatuserdata[$key]['end_date'] = date('m/d/Y', strtotime($data->end_date)); /**date_format */

            $start_date = date("m/d/Y", strtotime($data->start_date)); /** Convert to mm/dd/yyyy format */
            $end_date = date("m/d/Y", strtotime($data->end_date)); /** Convert to mm/dd/yyyy format */
            $formatuserdata[$key]['id'] = '<div class="dropdown custom_drop_down"><a class="dots" href="#" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa-solid fa-ellipsis-vertical"></i></a> <div class="dropdown-menu"> <a title="Edit Commission" class="edit_commission" data-id="'.$data->id.'" data-commissions="'.$data->commissions.'" data-status="'.$data->status.'" data-start_date="'.$start_date.'" data-end_date="'.$end_date.'" href="#" data-bs-toggle="modal" data-bs-target="#editCommissionModal"><i class="fa-regular fa-pen-to-square"></i>Edit</a></div></div>';
            
            if ($data->status == 1) {
                $formatuserdata[$key]['status'] = 'Active';
            } else {
                $formatuserdata[$key]['status'] = 'In-Active';
            }
        }

        return [
            'data' => $formatuserdata,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
        ];
    }
}
