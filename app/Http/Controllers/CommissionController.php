<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Catalog};


class CommissionController extends Controller
{
    public function index(Request $request, $commissionType, $id=null){
        if (!isset($id)) {
            $id = $request->query('id');
        }

        $setPageTitleArray = [
            'commission' => 'Commission',
        ];

        if (isset($id)) {
            $catalog = Catalog::query() 
            ->leftJoin('suppliers', 'catalog.supplier_id', '=', 'suppliers.id')
            ->leftJoin('catalog_details', 'catalog.id', '=', 'catalog_details.catalog_id')
            ->where('catalog.id', '=', $id)
            ->whereNotNull('catalog_details.table_value')
            ->select('catalog_details.table_key as key', 'catalog_details.table_value as value', 'catalog.sku as sku','catalog.price as price','suppliers.supplier_name as supplier_name','catalog.description as description')->get()->toArray();
            return view('admin.viewdetail',compact('catalog'));
        }

        return view('admin.commission.'. $commissionType .'', ['pageTitle' => $setPageTitleArray[$commissionType]]);
    }
}
