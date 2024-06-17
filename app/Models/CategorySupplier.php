<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CategorySupplier extends Model
{
    use HasFactory;

    protected $table = 'suppliers';

    protected $fillable = [
        'show',
        'category',
        'created_by',
        'supplier_name',
    ];

    static function supplierShowDataTable($filter=[]) {
        $supplierColumnArray = [
            'suppliers.supplier_name',
            'department.department',
            'name',
            'suppliers_detail.email',
            'suppliers_detail.phone',
            'suppliers.category',
            'suppliers_detail.status',
        ];

        $query = self::select([
            'suppliers.id as id',
            'suppliers.show as show',
            'suppliers.category as category',
            'suppliers_detail.email as email',
            'suppliers_detail.phone as phone',
            'suppliers_detail.status as status',
            'department.department as department',
            'suppliers.supplier_name as supplier_name',
            DB::raw("CONCAT(suppliers_detail.first_name, ' ', suppliers_detail.last_name) as name"),
        ])
        ->leftJoin('suppliers_detail', function($join) {
            $join->on('suppliers_detail.supplier', '=', 'suppliers.id')
                 ->where('suppliers_detail.main', '=', 1);
        })
        ->leftJoin('department', 'department.id', '=', 'suppliers_detail.department_id');

        /** Getting total records before adding filter */
        $totalRecords = $query->getQuery()->getCountForPagination();

        /** Add filter using by show column */
        if (isset($filter['show']) && $filter['show'] != 'all') {
            $query->where('suppliers.show', $filter['show']);
        }

        /** Search functionality */
        if (isset($filter['search']['value']) && !empty($filter['search']['value'])) {
            $searchTerm = $filter['search']['value'];
            $query->where(function ($q) use ($searchTerm, $supplierColumnArray) {
                foreach ($supplierColumnArray as $column) {
                    if ($column == 'name') {
                        $q->orWhere(DB::raw("CONCAT(suppliers_detail.first_name, ' ', suppliers_detail.last_name)"), 'LIKE', '%' . $searchTerm . '%');
                    } else {
                        $q->orWhere($column, 'LIKE', '%' . $searchTerm . '%');
                    }
                }
            });
        }

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
                'supplier_name' => '<a class="dots" href="'.route('supplier.show', ['id' => $suppliers->id]).'">'.$suppliers->supplier_name.'</a>',
                'department' => $suppliers->department,
                'name' => $suppliers->name,
                'email' => $suppliers->email,
                'phone' => $suppliers->phone,
                'category' => $suppliers->category,
                'status' => (($suppliers->status == 1) ? ('Active') : ((isset($suppliers->status)) ? ('In-active') : (''))),
                'show' => '<div class="form-check"><input data-id="'.$suppliers->id.'" class="form-check-input checkboxMain" type="checkbox" value="1" aria-label="..." '.(($suppliers->show == 0) ? ('checked') : ('')).'></div>',
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
