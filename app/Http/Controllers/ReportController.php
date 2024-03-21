<?php

namespace App\Http\Controllers;

use DB;
use League\Csv\Writer;
use App\Models\{Order, CategorySupplier, Account, SalesTeam};
use Symfony\Component\HttpFoundation\StreamedResponse;

use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request, $reportType, $id=null){
        // dd($$id);
        if (!isset($id)) {
            $id = $request->query('id');
        }
        
        $setPageTitleArray = [
            'business_report' => 'Business Report',
            'commission_report' => 'Commission Report',
            'supplier_report' => 'Supplier Rebate Report',
            'optimization_report' => 'Optimization Report',
            'consolidated_report' => 'Consolidated Supplier Report',
            'validation_rebate_report' => 'Validation Rebate Report',
        ];

        if(isset($id) && isset($reportType)){
            // Retrieve orders based on the join conditions
            $orders = DB::table('orders')

            ->leftjoin('accounts', 'orders.customer_number', '=', 'accounts.customer_number')
            ->leftjoin('suppliers', 'orders.supplier_id', '=', 'suppliers.id')
            ->select('orders.customer_number','orders.amount','orders.date','accounts.alies','suppliers.supplier_name','accounts.internal_reporting_name','accounts.qbr','accounts.spend_name')
            ->where('orders.id','=', $id)
            ->first();
            
           return view('admin.viewdetail',compact('orders'));
        }

        if ($reportType == 'business_report') {
            return view('admin.reports.'. $reportType .'', ['pageTitle' => $setPageTitleArray[$reportType], 'accountData' => Account::select('account_name')->groupBy('account_name')->get()]);
        } elseif($reportType == 'commission_report') {
            return view('admin.reports.'. $reportType .'', ['pageTitle' => $setPageTitleArray[$reportType], 'categorySuppliers' => CategorySupplier::where('show', 0)->where('show', '!=', 1)->get(), 'sales_rep' => SalesTeam::select(DB::raw('CONCAT(sales_team.first_name, " ", sales_team.last_name) as sales_rep'), 'commission.sales_rep as id')->leftJoin('commission', 'commission.sales_rep', '=', 'sales_team.id')->groupBy('commission.sales_rep')->get()]);
        } else {
            return view('admin.reports.'. $reportType .'', ['pageTitle' => $setPageTitleArray[$reportType], 'categorySuppliers' => CategorySupplier::where('show', 0)->where('show', '!=', 1)->get()]);
        }
    }

    public function dataFilter(Request $request){
        if ($request->ajax()) {
            $formatuserdata = Order::getFilterdData($request->all());
            return response()->json($formatuserdata);
        }
    }

    public function exportCsv(Request $request){
        /** Retrieve data based on the provided parameters */
        // $filter['start_date'] = $request->input('daterange.start');
        // $filter['end_date'] = $request->input('daterange.end');
        $filter['account_name'] = $request->input('account_name');

        // dd($filter);
        $csv = true;

        /** Fetch data using the parameters and transform it into CSV format */
        /** Replace this with your actual data fetching logic */
        $data = Order::getFilterdData($filter, $csv);

        /** Create a stream for output */
        $stream = fopen('php://temp', 'w+');

        /** Create a new CSV writer instance */
        $csvWriter = Writer::createFromStream($stream);
        
        $heading = $data['heading'];
        unset($data['heading']);

        /** Add column headings */
        $csvWriter->insertOne($heading);

        /** Add column headings */
        // $csvWriter->insertOne(['Id', 'Customer Number', 'Customer Name', 'Supplier Name', 'Amount', 'Date']);

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
        $response->headers->set('Content-Disposition', 'attachment; filename="BusinessData_'.now()->format('YmdHis').'.csv"');
  
        /** return $csvResponse; */
        return $response;
    }
    public function  Back()
    {
        $url = route('report.type',['reportType' => 'business_report']);
        return redirect($url);
    }

    public function supplierReportFilter(Request $request){
        if ($request->ajax()) {
            $formatuserdata = Order::getSupplierReportFilterdData($request->all());
            return response()->json($formatuserdata);
        }
    }

    public function supplierReportExportCsv(Request $request){
        /** Retrieve data based on the provided parameters */
        $filter['order'][0]['column'] = $request->input('column');
        $filter['order'][0]['dir'] = $request->input('order');
        $filter['quarter'] = $request->input('quarter');
        $filter['year'] = $request->input('year');
        $filter['supplier'] = $request->input('supplier');

        // dd($filter);
        $csv = true;

        /** Fetch data using the parameters and transform it into CSV format */
        /** Replace this with your actual data fetching logic */
        $data = Order::getSupplierReportFilterdData($filter, $csv);

        /** Create a stream for output */
        $stream = fopen('php://temp', 'w+');

        /** Create a new CSV writer instance */
        $csvWriter = Writer::createFromStream($stream);
        
        $heading = $data['heading'];
        unset($data['heading']);

        /** Add column headings */
        $csvWriter->insertOne($heading);

        /** Add column headings */
        // $csvWriter->insertOne(['Id', 'Customer Number', 'Customer Name', 'Supplier Name', 'Amount', 'Date']);

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
        $response->headers->set('Content-Disposition', 'attachment; filename="BusinessData_'.now()->format('YmdHis').'.csv"');
  
        /** return $csvResponse; */
        return $response;
    }

    public function commissionReportFilter(Request $request){
        if ($request->ajax()) {
            $formatuserdata = Order::getCommissionReportFilterdData($request->all());
            return response()->json($formatuserdata);
        }
    }
    public function commissionReportExportCsv(Request $request){
          /** Retrieve data based on the provided parameters */
          $filter['order'][0]['column'] = $request->input('column');
          $filter['order'][0]['dir'] = $request->input('order');
          $filter['quarter'] = $request->input('quarter');
          $filter['year'] = $request->input('year');
          $filter['supplier'] = $request->input('supplier');
          $filter['sales_rep'] = $request->input('sales_rep');
  
          // dd($filter);
          $csv = true;
  
          /** Fetch data using the parameters and transform it into CSV format */
          /** Replace this with your actual data fetching logic */
          $data = Order::getCommissionReportFilterdData($filter, $csv);
  
          /** Create a stream for output */
          $stream = fopen('php://temp', 'w+');
  
          /** Create a new CSV writer instance */
          $csvWriter = Writer::createFromStream($stream);
          
          $heading = $data['heading'];
          unset($data['heading']);
  
          /** Add column headings */
          $csvWriter->insertOne($heading);
  
          /** Add column headings */
          // $csvWriter->insertOne(['Id', 'Customer Number', 'Customer Name', 'Supplier Name', 'Amount', 'Date']);
  
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
          $response->headers->set('Content-Disposition', 'attachment; filename="BusinessData_'.now()->format('YmdHis').'.csv"');
    
          /** return $csvResponse; */
          return $response;
    }
}
