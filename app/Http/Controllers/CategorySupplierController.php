<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{CategorySupplier,Order};

class CategorySupplierController extends Controller
{
    public function index()
    {
      
        $model = new Order();
        // dd($model->random_invoice_num());
        // $categorySuppliers = CategorySupplier::all();
        //  dd($categorySuppliers);
        // return view('admin.export', compact('categorySuppliers'));
    }
}
