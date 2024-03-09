<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Account, Rebate};

class RebateController extends Controller
{
    public function index(Request $request, $rebateType, $id=null){
        if (!isset($id)) {
            $id = $request->query('id');
        }

        $setPageTitleArray = [
            'rebate' => 'Rebate',
        ];

        // if (isset($id)) {
        //     $catalog = Catalog::query() 
        //     ->leftJoin('suppliers', 'catalog.supplier_id', '=', 'suppliers.id')
        //     ->leftJoin('catalog_details', 'catalog.id', '=', 'catalog_details.catalog_id')
        //     ->where('catalog.id', '=', $id)
        //     ->whereNotNull('catalog_details.table_value')
        //     ->select('catalog_details.table_key as key', 'catalog_details.table_value as value', 'catalog.sku as sku','catalog.price as price','suppliers.supplier_name as supplier_name','catalog.description as description')->get()->toArray();
        //     return view('admin.viewdetail',compact('catalog'));
        // }

        return view('admin.rebate.'. $rebateType .'', ['pageTitle' => $setPageTitleArray[$rebateType]]);
    }

    public function getRebateWithAjax(Request $request){
        if ($request->ajax()) {
            $formatuserdata = Account::getFilterdRebateData($request->all());
            return response()->json($formatuserdata);
        }
    }

    public function rebateUpdate(Request $request){
        if ($request->ajax()) {
            $rebate = Rebate::where('account_number', $request->account_number)->first();

            /** Check if the record exists */
            if($rebate) {
                /** Update the existing record with validated data */
                $rebate->update(['account_number' => $request->input('account_number'),
                'volume_rebate' => $request->input('volume_rebate'),
                'incentive_rebate' => $request->input('incentive_rebate'),
                ]);
            
                return response()->json(['success' => 'Rebate updated successfully'], 200);
            } else {
                /** Create a new record with validated data */
                $rebate = Rebate::create(['account_number' => $request->input('account_number'),
                'volume_rebate' => $request->input('volume_rebate'),
                'incentive_rebate' => $request->input('incentive_rebate'),
                ]);
            
                return response()->json(['success' => 'Record added successfully'], 200);
            }
        }
    }
}
