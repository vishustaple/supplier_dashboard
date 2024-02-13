<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Catalog extends Model
{
    use HasFactory;

    protected $table = 'catalog';

     /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'sku',
        'price',
        'created_by',
        'supplier_id',
        'description',
    ];

    public static function getFilterdCatalogData($filter=[], $csv=false){
        $orderColumnArray = [
            0 => 'suppliers.supplier_name',
            1 => 'catalog.sku',
            2 => 'catalog.description',
            3 => 'catalog.price',
        ];
    
        $query = self::query() // Replace YourModel with the actual model you are using for the data
        ->leftJoin('suppliers', 'catalog.supplier_id', '=', 'suppliers.id')

        ->select('catalog.id as id', 'catalog.sku as sku' ,'catalog.description as description' ,'suppliers.supplier_name as supplier_name' ,'catalog.price as price'); // Adjust the column names as needed

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
            $formatuserdata[$key]['sku'] = $data->sku;
            $formatuserdata[$key]['description'] = $data->description;
            $formatuserdata[$key]['supplier_name'] = $data->supplier_name;
            $formatuserdata[$key]['price'] = '$'.$data->price;
            if ($csv == false) {    
                $formatuserdata[$key]['id'] = '<a class="btn btn-primary" title="View Details" href= '.route('catalog.list', ['catalogType' => 'Catalog List','id' => $data->id]).'><i class="fa-regular  fa-eye"></i></a>';
            }
            // $formatuserdata[$key]['date'] = date_format(date_create($data->date), 'm/d/Y');
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
