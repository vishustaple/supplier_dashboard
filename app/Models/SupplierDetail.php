<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;

class SupplierDetail extends Model
{
    use HasFactory;

    protected $table = 'suppliers_detail';

    protected $fillable = [
        'main',
        'title',
        'email',
        'phone',
        'status',
        'supplier',
        'last_name',
        'first_name',
    ];

    public static function getSupplierDetailFilterdData($filter = [], $csv = false) {
		/** Define column array for ordering the rows and searching the rows */
		$orderColumnArray = ['main', 'name', 'title', 'email', 'phone', 'status'];

		$query = self::query() // Replace YourModel with the actual model you are using for the data   
		->select(['id', 'main', 'email', 'first_name', 'last_name', 'phone', 'title', 'status', DB::raw("CONCAT(first_name, ' ', last_name) AS name")]);

		$totalRecords = $query->getQuery()->getCountForPagination();

		/** Search functionality */
		if (isset($filter['search']['value']) && !empty($filter['search']['value'])) {
			$searchTerm = $filter['search']['value'];

			$query->where(function ($q) use ($searchTerm, $orderColumnArray) {
				foreach ($orderColumnArray as $column) {
					$q->orWhere($column, 'LIKE', '%' . $searchTerm . '%');
				}
			});            
		}

		if (isset($filter['supplier_id']) && !empty($filter['supplier_id'])) {
			$query->where('supplier', $filter['supplier_id']);
		}
		
		/** Get total records count (without filtering) */
		if (isset($filter['order'][0]['column']) && isset($orderColumnArray[$filter['order'][0]['column']]) && isset($filter['order'][0]['dir'])) {
			/** Order by column and direction */
			$query->orderBy($orderColumnArray[$filter['order'][0]['column']], $filter['order'][0]['dir']);
		} else {
			$query->orderBy($orderColumnArray[0], 'asc');
		}

		$filteredRecords = $query->getQuery()->getCountForPagination();

		$queryData = $query->when(isset($filter['start']) && isset($filter['length']), function ($query) use ($filter) {
			return $query->skip($filter['start'])->take($filter['length']);
		})->get();

		$finalArray = [];
		foreach ($queryData as $key => $value) {
			$finalArray[$key]['name'] = $value->name;
			$finalArray[$key]['email'] = $value->email;
			$finalArray[$key]['phone'] = $value->phone;
			$finalArray[$key]['title'] = $value->title;
			$finalArray[$key]['status'] = (($value->status == 1) ? ('Active') : ((isset($value->status)) ? ('In-active') : ('')));
			$finalArray[$key]['main'] = '<div class="form-check"><input data-id="'.$value->id.'" class="form-check-input checkboxMain" type="checkbox" value="1" aria-label="..." '.(($value->main == 1) ? ('checked') : ('')).'></div>';
			$finalArray[$key]['id'] = '<div class="dropdown custom_drop_down"><a class="dots" href="#" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa-solid fa-ellipsis-vertical"></i></a><div class="dropdown-menu"><a title="Edit Supplier" class="" id="edit_supplier" data-id="'.$value->id.'" data-first_name="'.$value->first_name.'" data-last_name="'.$value->last_name.'" data-email="'.$value->email.'" data-phone="'.$value->phone.'" data-main="'.$value->main.'" data-title="'.$value->title.'" data-status="'.$value->status.'" href="#" data-bs-toggle="modal" data-bs-target="#editSupplierModal"><i class="fa-regular fa-pen-to-square"></i>Edit</a><a hrefe="#" data-id="'. $value->id .'" class="remove" title="Remove Account"><i class="fa-solid fa-trash"></i>Remove</a></div></div>';
		}
		// dd($query->toSql(), $query->getBindings());
		// dd($finalArray);

		// Return the result along with total and filtered counts
		return [
			'data' => $finalArray,
			'recordsTotal' => $totalRecords,
			'recordsFiltered' => $filteredRecords,
		];
	}
}
