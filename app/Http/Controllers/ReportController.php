<?php

namespace App\Http\Controllers;
use DataTables;
use App\Models\{Account, Order, OrderDetails, UploadedFiles, CategorySupplier};
use Illuminate\Support\Facades\Response;

use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index($reportType){
        if ($reportType == 'csv') {
            $reportType ='business_report';
        }

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
            $formatuserdata = Order::getFilterdData($request->all());
            return response()->json($formatuserdata);
        }
    }

    public function exportCsv(Request $request){
        // Retrieve data based on the provided parameters
        $startDate = $request->input('daterange.start');
        $endDate = $request->input('daterange.end');
        $supplierId = $request->input('supplierId');

        $filter['start_date'] = $startDate;
        $filter['$end_date'] = $endDate;
        $filter['supplierId'] = $supplierId; 

        $csv = true;

        // Fetch data using the parameters and transform it into CSV format
        // Replace this with your actual data fetching logic
        $csvData = Order::getFilterdData($filter, $csv);

        // Generate CSV file and set appropriate headers
        $csvFileName = 'export.csv';
        $headers = array(
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $csvFileName . '"',
        );

        // Create CSV response
        $csvResponse = Response::make($csvData, 200, $headers);

        return $csvResponse;
    }
}
