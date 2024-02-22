<?php

use App\Models\CategorySupplier;

if (!function_exists('getSupplierName')) {
    function getSupplierName($id) {
        $supplierName = CategorySupplier::where('id',$id)->value('supplier_name');
        return  $supplierName;
    }
}

