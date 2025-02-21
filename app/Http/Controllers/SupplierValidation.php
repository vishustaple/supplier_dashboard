<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{CategorySupplier, SupplierValidationAttachments};

class SupplierValidation extends Controller
{
    public function index() {
        $categorySuppliers = CategorySupplier::where('show', 0)->where('show', '!=', 1)->get();
        $pageTitle = "Import Supplier Rebate File";
 
        return view('admin.supplier_validation_export',compact('categorySuppliers', 'pageTitle'));
    }

    public function getSupplierValidationExportWithAjax(Request $request) {
        if ($request->ajax()) {
            $formatuserdata = SupplierValidationAttachments::getSupplierValidationFilterdExcelData($request->all());
            return response()->json($formatuserdata);
        }
    }

    public function 
}
