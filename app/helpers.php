<?php

use App\Models\CategorySupplier;

if (!function_exists('getsuppliername')) {
    function getsuppliername($id) {
        $suppliername=CategorySupplier::where('id',$id)->value('supplier_name');
        return  $suppliername;
    }
}