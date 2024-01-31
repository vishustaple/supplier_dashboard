<?php

namespace App\Http\Controllers;
use App\Models\{Account, Order, OrderDetails, UploadedFiles};

use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(){
        $pageTitle = "Supplier Report";
        return view('admin.report', ['pageTitle' => $pageTitle]);
    }

    public function dataFilter(){
        
    }
}
