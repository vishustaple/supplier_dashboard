<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;

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
            1 => 'master_account_detail.account_number',
            2 => 'suppliers.supplier_name',
            3 => 'commission.commission',
            4 => 'commission.sales_rep',
            5 => 'commission.start_date',
            6 => 'commission.end_date',
        ];
        // $csv = true;
        // if ($csv) {
            $query = self::query() // Replace YourModel with the actual model you are using for the data
            ->leftJoin('suppliers', 'commission.supplier', '=', 'suppliers.id')
            ->leftJoin('master_account_detail', 'master_account_detail.account_number', '=', 'commission.account_name')
            ->leftJoin('sales_team', 'sales_team.id', '=', 'commission.sales_rep')

            ->select(
                'commission.account_name as account_name',
                'master_account_detail.account_number as account_number',
                'suppliers.supplier_name as supplier_name',
                'commission.commission as commission',
                'commission.start_date as start_date',
                DB::raw("CONCAT(sales_team.first_name, ' ', sales_team.last_name) AS sales_rep"),
                'commission.end_date as end_date',
            ); // Adjust the column names as needed
        // } else {
        //     $query = self::query() // Replace YourModel with the actual model you are using for the data
        //     ->leftJoin('suppliers', 'catalog.supplier_id', '=', 'suppliers.id')
        //     ->leftJoin('master_account_detail', 'master_account_detail.account_number', '=', 'commission.account_name')
        //     ->select('commission.account_name', 'master_account_detail.account_number', 'suppliers.supplier_name', 'commission.start_date', 'commission.end_date', 'catalog.price',); // Adjust the column names as needed
        // }

        // Search functionality
        if (isset($filter['search']['value']) && !empty($filter['search']['value'])) {
            $searchTerm = $filter['search']['value'];

            $query->where(function ($q) use ($searchTerm, $orderColumnArray) {
                foreach ($orderColumnArray as $column) {
                    $q->orWhere($column, 'LIKE', '%' . $searchTerm . '%');
                }
            });

            $query->orWhere('suppliers.supplier_name', 'LIKE', '%' . $searchTerm . '%');
            $query->orWhere('master_account_detail.account_number', 'LIKE', '%' . $searchTerm . '%');
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
        if ($csv) {
            // $formatuserdata
            // foreach ($filteredData->toArray() as $key => $value) {
            //     $formatuserdata[$value['id']][] = [
            //         'table_key' => $value['table_key'],
            //         'table_value' => $value['table_value'],
            //     ];
            // }

            foreach ($formatuserdata as $key => $value) {
                for ($i=0; $i < count($value); $i++) {
                    // if ($value[$i]['table_key'] == 'SKUNUMBER') {
                    //     $finalArray[$key][$value[$i]['table_key']] = "\t" .$value[$i]['table_value'];
                    // } else {
                    //     $finalArray[$key][$value[$i]['table_key']] = $value[$i]['table_value'];
                    // }
                    
                    if (!isset($arrayKeySet)) {
                        $keyArray[] = ucwords(str_replace("_", ' ', $value[$i]['table_key']));
                    }
                }

                if (isset($keyArray)) {
                    $arrayKeySet = true;
                }
            }

            $finalArray['heading'] = $keyArray;
            return $finalArray;
        } else {
            foreach ($filteredData as $key => $data) {
                $formatuserdata[$key]['account_name'] = $data->account_name;
                $formatuserdata[$key]['account_number'] = $data->account_number;
                $formatuserdata[$key]['sales_rep'] = $data->sales_rep;
                $formatuserdata[$key]['supplier_name'] = $data->supplier_name;
                $formatuserdata[$key]['commission'] = $data->commission.'%';
                $formatuserdata[$key]['start_date'] = date('m/d/Y', strtotime($data->start_date));//date_format(date_create($data->start_date), 'm/d/Y');
                $formatuserdata[$key]['end_date'] = date('m/d/Y', strtotime($data->end_date)); //date_format(date_create($data->end_date), 'm/d/Y');
                // $formatuserdata[$key]['id'] = '<a class="btn btn-primary" title="View Details" href= '.route('catalog.list', ['catalogType' => 'Catalog List','id' => $data->id]).'><i class="fa-regular  fa-eye"></i></a>';
            }
        }

        // if ($csv == true) {
        //     return $formatuserdata;
        // } else {
            // Return the result along with total and filtered counts
            return [
                'data' => $formatuserdata,
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $totalRecords,
            ];
        // }
    }
}
