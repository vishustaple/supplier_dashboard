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

    public function showPermissions(){
        return $this->belongsToMany(ShowPermissions::class, 'supplier_permissions');
    }

    public function hasPermission($permission){
        return $this->showPermissions()->where('permission_name', $permission)->exists();
    }

    static function supplierShowDataTable($filter=[]) {
        $supplierColumnArray = [
            'suppliers.supplier_name',
            'department.department',
            'name',
            'supplier_contacts.email',
            'supplier_contacts.phone',
            'suppliers.category',
            'supplier_contacts.status',
        ];

        $query = self::select([
            'suppliers.id as id',
            'suppliers.hide_show as hide_show',
            'suppliers.category as category',
            'supplier_contacts.email as email',
            'supplier_contacts.phone as phone',
            'supplier_contacts.status as status',
            'department.department as department',
            'suppliers.supplier_name as supplier_name',
            'supplier_fields.id as manage_columns_id',
            DB::raw("CONCAT(supplier_contacts.first_name, ' ', supplier_contacts.last_name) as name"),
        ])
        ->leftJoin('supplier_contacts', function($join) {
            $join->on('supplier_contacts.supplier', '=', 'suppliers.id')
                 ->where('supplier_contacts.main', '=', 1);
        })
        ->leftJoin('department', 'department.id', '=', 'supplier_contacts.department_id')
        ->leftJoin('supplier_fields', 'supplier_fields.supplier_id', '=', 'suppliers.id');

        /** Getting total records before adding filter */
        $totalRecords = $query->getQuery()->getCountForPagination();

        /** Add filter using by show column */
        if (isset($filter['show']) && $filter['show'] != 'all') {
            $query->where('suppliers.hide_show', $filter['show']);
        }

        /** Search functionality */
        if (isset($filter['search']['value']) && !empty($filter['search']['value'])) {
            $searchTerm = $filter['search']['value'];
            $query->where(function ($q) use ($searchTerm, $supplierColumnArray) {
                foreach ($supplierColumnArray as $column) {
                    if ($column == 'name') {
                        $q->orWhere(DB::raw("CONCAT(supplier_contacts.first_name, ' ', supplier_contacts.last_name)"), 'LIKE', '%' . $searchTerm . '%');
                    } else {
                        $q->orWhere($column, 'LIKE', '%' . $searchTerm . '%');
                    }
                }
            });
        }

        $query->groupBy('suppliers.id');
        
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
                'show' => '<div class="form-check"><input data-id="'.$suppliers->id.'" class="form-check-input checkboxMain" type="checkbox" value="1" aria-label="..." '.(($suppliers->hide_show == 1) ? ('checked') : ('')).'></div>',
                'edit' => '<div class="dropdown custom_drop_down"><a class="dots" href="#" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa-solid fa-ellipsis-vertical"></i></a> <div class="dropdown-menu"><a title="Edit Account" class="" id="edit_account" data-id="'.$suppliers->id.'" data-supplier_name="'.$suppliers->supplier_name.'" data-category="'.$suppliers->category.'" data-show="'.$suppliers->hide_show.'" href="#" data-bs-toggle="modal" data-bs-target="#editSupplierModal"><i class="fa-regular fa-pen-to-square"></i>Edit Supplier</a><a title="Edit File Format" class="" id="edit_format" data-id="'.$suppliers->id.'" href="#" data-bs-toggle="modal" data-bs-target="'.(!empty(($suppliers->manage_columns_id)) ? ('#editSupplierFileFormatModal') : ('#addSupplierFileFormatModal')).'"><i class="fa fa-file-excel" aria-hidden="true"></i>'.(!empty(($suppliers->manage_columns_id)) ? ('Edit') : ('Add')).' File Format</a>'.
                (!empty(($suppliers->manage_columns_id)) ? ('<a title="Delete File Format" class="delete_format" data-id="'.$suppliers->id.'" href="#"><i class="fa fa-trash" aria-hidden="true"></i>Delete File Format</a>') : ('')).'<a title="Delete File Format" data-bs-toggle="modal" data-bs-target="#editSupplierPermission" class="supplier_show_permissions" data-id="'.$suppliers->id.'" href="#"><i class="fas fa-user-shield"></i>Supplier Show Permissions</a></div></div>'
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
