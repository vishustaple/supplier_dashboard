<?php

namespace App\Http\Controllers;
use DataTables;
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

    public function dataFilter(Request $request){
        if ($request->ajax()) {
            $data = User::latest()->get();
            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $btn = '<button type="button" class="btn btn-primary">Action</button>';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }
    }
}
