<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\{Model, Factories\HasFactory};

class CategorySupplier extends Model
{
    use HasFactory;

    protected $connection = 'second_db';

    protected $table = 'suppliers';

    protected $fillable = [
        'show',
        'category',
        'created_by',
        'supplier_name',
    ];

    static function supplierCatalogShowDataTable($filter=[]) {
        $supplierColumnArray = [
            'suppliers.supplier_name',
            'suppliers.category',
        ];

        $query = self::select([
            'suppliers.id as id',
            'suppliers.category as category',
            'suppliers.supplier_name as supplier_name',
            'catalog_supplier_fields.id as manage_columns_id',
        ])
        ->leftJoin('catalog_supplier_fields', function($join) {
            $join->on('catalog_supplier_fields.supplier_id', '=', 'suppliers.id')
                 ->where('catalog_supplier_fields.deleted', '=', 0);
        });

        /** Getting total records before adding filter */
        $totalRecords = $query->getQuery()->getCountForPagination();

        /** Search functionality */
        if (isset($filter['search']['value']) && !empty($filter['search']['value'])) {
            $searchTerm = $filter['search']['value'];
            $query->where(function ($q) use ($searchTerm, $supplierColumnArray) {
                foreach ($supplierColumnArray as $column) {
                    $q->orWhere($column, 'LIKE', '%' . $searchTerm . '%');
                }
            });
        }

        $query->groupBy(['suppliers.id']);
        
        $filteredRecords = $query->getQuery()->getCountForPagination();

        /** Order by column and direction */
        if (isset($filter['order'][0]['column']) && isset($supplierColumnArray[$filter['order'][0]['column']]) && isset($filter['order'][0]['dir'])) {
            $query->orderBy($supplierColumnArray[$filter['order'][0]['column']], $filter['order'][0]['dir']);
        } else {
            $query->orderBy($supplierColumnArray[0], 'asc');
        }

        /** Get paginated results based on start, length */
        if (isset($filter['start']) && isset($filter['length'])) {
            $categorySuppliers = $query->skip($filter['start'])->take($filter['length'])->get();
        } else {
            $categorySuppliers = $query->get();
        }

        /** Making final array for return */
        $formattedData = [];
        foreach ($categorySuppliers as $suppliers) {
            $formattedData[] = [
                'supplier_name' => $suppliers->supplier_name,
                'category' => $suppliers->category,
                'edit' => '<div class="dropdown custom_drop_down"><a class="dots" href="#" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa-solid fa-ellipsis-vertical"></i></a> <div class="dropdown-menu"><a title="Edit File Format" class="" id="edit_format" data-id="'.$suppliers->id.'" href="#" data-bs-toggle="modal" data-bs-target="'.(!empty(($suppliers->manage_columns_id)) ? ('#editSupplierFileFormatModal') : ('#addSupplierFileFormatModal')).'"><i class="fa fa-file-excel" aria-hidden="true"></i>'.(!empty(($suppliers->manage_columns_id)) ? ('Edit') : ('Add')).' File Format </a>'.
                (!empty(($suppliers->manage_columns_id)) ? ('<a title="Delete File Format" class="delete_format" data-id="'.$suppliers->id.'" href="#"><i class="fa fa-trash" aria-hidden="true"></i>Delete File Format </a>') : ('')).'</div></div>'
            ];
        }
        
        /** Final data returning */
        return [
            'data' => $formattedData,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
        ];
    }
}
