<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
       
        $query = self::query() /** Replace YourModel with the actual model you are using for the data */
        ->leftJoin('suppliers', 'catalog.supplier_id', '=', 'suppliers.id')

        ->select(
            'catalog.id as id',
            'catalog.sku as sku',
            'catalog.price as price',
            'catalog.description as description',
            'suppliers.supplier_name as supplier_name',
        ); /** Adjust the column names as needed */

        /** Get total records count (without filtering) */
        $totalRecords = $query->count();

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
        
        /** Get paginated results based on start, length */
        if (isset($filter['start']) && isset($filter['length'])) {
            $filteredData = $query->skip($filter['start'])->take($filter['length'])->get();
        } else {
            $filteredData = $query->get();
        }
        
        /** Get filtered records count */
        $filteredRecords = $query->getQuery()->getCountForPagination();

        /** Print the SQL query */
        // dd($query->toSql());
        
        $formatuserdata=[];
        foreach ($filteredData as $key => $data) {
            $formatuserdata[$key]['sku'] = $data->sku;
            $formatuserdata[$key]['price'] = '$'.$data->price;    
            $formatuserdata[$key]['description'] = $data->description;
            $formatuserdata[$key]['supplier_name'] = $data->supplier_name;
            $formatuserdata[$key]['id'] = '<a class="btn btn-primary" title="View Details" href= '.route('catalog.list', ['catalogType' => 'Catalog List','id' => $data->id]).'><i class="fa-regular  fa-eye"></i></a>';
        }

        return [
            'data' => $formatuserdata,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
        ];
    }
}
