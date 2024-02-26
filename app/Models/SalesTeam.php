<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesTeam extends Model
{
    use HasFactory;
    
    protected $table = 'sales_team';

    protected $fillable = [
        'email',
        'phone',
        'status',
        'last_name',
        'first_name',
    ];
    
    public static function getFilterdSalesData($filter=[], $csv=false){
        $orderColumnArray = [
            0 => 'sales_team.first_name',
            1 => 'sales_team.email',
            2 => 'sales_team.phone',
            3 => 'sales_team.status',
        ];

        $query = self::query() // Replace YourModel with the actual model you are using for the data
        ->select(
            'sales_team.id as id',
            'sales_team.first_name as first_name' ,
            'sales_team.last_name as last_name',
            'sales_team.email as email' ,
            'sales_team.phone as phone',
            'sales_team.status as status' ,
        ); // Adjust the column names as needed

        // Search functionality
        if (isset($filter['search']['value']) && !empty($filter['search']['value'])) {
            $searchTerm = $filter['search']['value'];

            $query->where(function ($q) use ($searchTerm, $orderColumnArray) {
                foreach ($orderColumnArray as $column) {
                    if ($column == "sales_team.first_name") {
                        continue;
                    }

                    
                    $q->orWhere($column, 'LIKE', '%' . $searchTerm . '%');
                }
            });
          
            $query->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%$searchTerm%"]);
            
            // $query->whereRaw("OR CONCAT(sales_team.first_name, ' ', sales_team.last_name) LIKE ?", ["%".$searchTerm."%"]);
            // $query->orWhere('sales_team.last_name', 'LIKE', '%' . $searchTerm . '%');
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
            $formatuserdata[$key]['name'] = $data->first_name.' '.$data->last_name;
            $formatuserdata[$key]['email'] = $data->email;
            $formatuserdata[$key]['phone'] = $data->phone;
            $formatuserdata[$key]['status'] = ($data->status == 1) ? ("Active") : ("In-Active");    
            $formatuserdata[$key]['action'] = '<div class="dropdown custom_drop_down"><a class="dots" href="#" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa-solid fa-ellipsis-vertical"></i></a> <div class="dropdown-menu"><a class=" " title="View Details" href= '.route('sales.index', ['id' => $data->id]).'><i class="fa-regular  fa-eye"></i>View</a> <a title="Edit SalesTeam" class=" " href= '.route('sales.edit', ['id' => $data->id,'routename' => 'sales']).' ><i class="fa-regular fa-pen-to-square"></i>Edit</a><a hrefe="#" data-id="'. $data->id .'" class="remove" title="Remove Sales"><i class="fa-solid fa-trash"></i>Remove</a></div></div>';
        }
        
        // echo"<pre>";
        // print_r($formatuserdata);
        // die;
        // Return the result along with total and filtered counts
        return [
            'data' => $formatuserdata,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
        ];
    }
}
