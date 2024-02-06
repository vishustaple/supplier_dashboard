<?php

namespace App\Http\Controllers;
use DataTables;
use App\Models\{Account, Order, OrderDetails, UploadedFiles, CategorySupplier};
use League\Csv\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;
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
        $data = Order::getFilterdData($filter, $csv);

        // // Generate CSV file and set appropriate headers
        // $csvFileName = 'export.csv';
        // $headers = array(
        //     'Content-Type' => 'text/csv',
        //     'Content-Disposition' => 'attachment; filename="' . $csvFileName . '"',
        // );

        // // Create CSV response
        // $csvResponse = Response::make($csvData, 200, $headers);

          // Create a stream for output
          $stream = fopen('php://temp', 'w+');

          // Create a new CSV writer instance
          $csvWriter = Writer::createFromStream($stream);
  
          // Insert the data into the CSV
          $csvWriter->insertAll($data);
  
          // Rewind the stream pointer
          rewind($stream);
  
          // Create a streamed response with the CSV data
          $response = new StreamedResponse(function () use ($stream) {
              fpassthru($stream);
          });
  
          // Set headers for CSV download
          $response->headers->set('Content-Type', 'text/csv');
          $response->headers->set('Content-Disposition', 'attachment; filename="data.csv"');
  
          return $response;
        // return $csvResponse;
    }
}
