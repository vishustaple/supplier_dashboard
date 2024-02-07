<?php

namespace App\Http\Controllers;

use DataTables;
use League\Csv\Writer;
use App\Models\{Order, CategorySupplier};
use Symfony\Component\HttpFoundation\StreamedResponse;

use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index($reportType){
        $setPageTitleArray = [
            'business_report' => 'Business Report',
            'commission_report' => 'Commission Report',
            'supplier_report' => 'Supplier Rebate Report',
            'optimization_report' => 'Optimization Report',
            'consolidated_report' => 'Consolidated Supplier Report',
            'validation_rebate_report' => 'Validation Rebate Report',
        ];
        
        return view('admin.reports.'. $reportType .'', ['pageTitle' => $setPageTitleArray[$reportType], 'categorySuppliers' => CategorySupplier::all()]);
    }

    public function dataFilter(Request $request){
        if ($request->ajax()) {
            $formatuserdata = Order::getFilterdData($request->all());
            return response()->json($formatuserdata);
        }
    }

    public function exportCsv(Request $request){
        /** Retrieve data based on the provided parameters */
        $filter['start_date'] = $request->input('daterange.start');
        $filter['end_date'] = $request->input('daterange.end');
        $filter['supplierId'] = $request->input('supplierId');

        // dd($filter);
        $csv = true;

        /** Fetch data using the parameters and transform it into CSV format */
        /** Replace this with your actual data fetching logic */
        $data = Order::getFilterdData($filter, $csv);

        /** Create a stream for output */
        $stream = fopen('php://temp', 'w+');

        /** Create a new CSV writer instance */
        $csvWriter = Writer::createFromStream($stream);
        
        /** Add column headings */
        $csvWriter->insertOne(['Id', 'Customer Number', 'Customer Name', 'Supplier Name', 'Amount', 'Date']);

        /** Insert the data into the CSV */
        $csvWriter->insertAll($data);

        /** Rewind the stream pointer */
        rewind($stream);

        /** Create a streamed response with the CSV data */
        $response = new StreamedResponse(function () use ($stream) {
            fpassthru($stream);
        });

        /** Set headers for CSV download */
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename=""BusinessData'.now()->format('YmdHis').'.csv".csv"');
  
        /** return $csvResponse; */
        return $response;
    }
}
