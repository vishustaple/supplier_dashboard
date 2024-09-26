<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Commission extends Model
{
    use HasFactory;

    protected $table = 'commission';

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
        'commission',
        'start_date',
        'account_name',
    ];

    public static function getFilterdCommissionData($filter=[], $csv=false){
        $orderColumnArray = [
            0 => 'commission.account_name',
            1 => 'suppliers.supplier_name',
            2 => 'commission.sales_rep',
            3 => 'commission.commission',
            4 => 'commission.start_date',
            5 => 'commission.end_date',
            6 => 'commission.status',
            7 => 'commission.id',
        ];
   
        $query = self::query() /** Replace YourModel with the actual model you are using for the data */
        ->leftJoin('suppliers', 'commission.supplier', '=', 'suppliers.id')
        ->leftJoin('sales_team', 'sales_team.id', '=', 'commission.sales_rep')
        ->select(
            'commission.id as id',
            'commission.status as status',
            'commission.end_date as end_date',
            'commission.commission as commission',
            'commission.start_date as start_date',
            'commission.account_name as account_name',
            'suppliers.supplier_name as supplier_name',
            DB::raw("CONCAT(sales_team.first_name, ' ', sales_team.last_name) AS sales_rep"),
        ); /** Adjust the column names as needed */
    
        $totalRecords = $query->getQuery()->getCountForPagination();

        /** Search functionality */
        if (isset($filter['search']['value']) && !empty($filter['search']['value'])) {
            $searchTerm = $filter['search']['value'];
            $query->where(function ($q) use ($searchTerm, $orderColumnArray) {
                foreach ($orderColumnArray as $column) {
                    if ($column == 'commission.sales_rep') {
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
            $formatuserdata[$key]['commission'] = $data->commission.'%';
            $formatuserdata[$key]['supplier_name'] = $data->supplier_name;
            $formatuserdata[$key]['customer_name'] = $data->customer_name;
            $formatuserdata[$key]['customer_number'] = $data->customer_number;
            $formatuserdata[$key]['start_date'] = date('m/d/Y', strtotime($data->start_date));
            $formatuserdata[$key]['end_date'] = date('m/d/Y', strtotime($data->end_date)); /**date_format */

            $start_date = date("m/d/Y", strtotime($data->start_date)); /** Convert to mm/dd/yyyy format */
            $end_date = date("m/d/Y", strtotime($data->end_date)); /** Convert to mm/dd/yyyy format */
            
            /** To create a date range for the same day, just concatenate the start date */
            $date_range = $start_date . " - " . $end_date;

            $formatuserdata[$key]['id'] = '<div class="dropdown custom_drop_down"><a class="dots" href="#" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa-solid fa-ellipsis-vertical"></i></a> <div class="dropdown-menu"> <a title="Edit Commission" class="edit_commission" data-id="'.$data->id.'" data-date="'.$date_range.'" data-commission="'.$data->commission.'" data-status="'.$data->status.'" href="#" data-bs-toggle="modal" data-bs-target="#editCommissionModal"><i class="fa-regular fa-pen-to-square"></i>Edit</a></div></div>';
            
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
