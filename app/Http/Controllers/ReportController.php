<?php

namespace App\Http\Controllers;
use App\Models\{Account, Order, OrderDetails, UploadedFiles, CategorySupplier};

use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index($reportType){
        $setPageTitleArray = [
            'business_report' => 'Business Report',
            'optimization_report' => 'Optimization Report',
            'consolidated_report' => 'Consolidated Supplier Report',
            'supplier_report' => 'Supplier Rebate Report',
            'validation_rebate_report' => 'Validation Rebate Report',
            'commission_report' => 'Commission Report'
        ];

        $pageTitle = $setPageTitleArray[$reportType];
        
        return view('admin.reports.'. $reportType .'', ['pageTitle' => $pageTitle, 'categorySuppliers' => CategorySupplier::all()]);
    }

    public function dataFilter(){
        
    }
}
