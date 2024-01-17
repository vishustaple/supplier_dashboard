<?php

namespace App\Http\Controllers;
use App\Models\{CategorySupplier,Order};
use Illuminate\Http\Request;

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
