<?php

namespace App\Http\Controllers;

use Mpdf\Mpdf;
use League\Csv\Writer;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;
use DateTime;
use App\Models\{
    Order,
    Account,
    SalesTeam,
    CategorySupplier,
    CommissionRebateDetailHtml
};

class ReportController extends Controller
{
    public function __construct(Request $request){
        $setPageTitleArray = [
            'business_report' => 'Business Report',
            'commission_report' => 'Commission Report',
            'supplier_report' => 'Supplier Rebate Report',
            'optimization_report' => 'Quarter Report',
            'consolidated_report' => 'Consolidated Supplier Report',
            'validation_rebate_report' => 'Validation Rebate Report',
            'operational_anomaly_report' => 'Operational Anomaly Report',
        ];

        // $setPageTitleArray1 = [
        //     'business_report' => ['dataFilter', 'exportCsv'],
        //     'commission_report' => ['getCommissionsWithAjax', 'commissionReportFilter', 'commissionReportExportCsv', 'approvedUpdate', 'paidUpdate', 'downloadSampleCommissionFile'],
        //     'supplier_report' => ['supplierReportFilter', 'supplierReportExportCsv'],
        //     'consolidated_report' => ['consolidatedReportFilter', 'exportConsolidatedCsv', 'exportConsolidatedDownload'],
        //     'optimization_report' => '',
        //     'validation_rebate_report' => '',
        // ];
        $reportType = $request->route('reportType');
        if (isset($reportType)) {
            $this->middleware('permission:'.$setPageTitleArray[$request->route('reportType')])->only(['index']);
        }

        $this->middleware('permission:Business Report')->only(['dataFilter', 'exportCsv']);
        $this->middleware('permission:Commission Report')->only(['getCommissionsWithAjax', 'commissionReportFilter', 'commissionReportExportCsv', 'approvedUpdate', 'paidUpdate', 'downloadSampleCommissionFile']);
        $this->middleware('permission:Supplier Rebate Report')->only(['supplierReportFilter', 'supplierReportExportCsv']);
        $this->middleware('permission:Consolidated Supplier Report')->only(['consolidatedReportFilter', 'exportConsolidatedCsv', 'exportConsolidatedDownload']);
    }

    public function index(Request $request, $reportType, $id=null){
        if (!isset($id)) {
            $id = $request->query('id');
        }
        
        $setPageTitleArray = [
            'business_report' => 'Business Report',
            'commission_report' => 'Commission Report',
            'supplier_report' => 'Supplier Rebate Report',
            'optimization_report' => 'Quarter Report',
            'consolidated_report' => 'Consolidated Supplier Report',
            'validation_rebate_report' => 'Validation Rebate Report',
            'operational_anomaly_report' => 'Operational Anomaly Report',
        ];

        if(isset($id) && isset($reportType)){
            /** Retrieve orders based on the join conditions */
            $orders = DB::table('orders')

            ->leftjoin('accounts', 'orders.customer_number', '=', 'accounts.customer_number')
            ->leftjoin('suppliers', 'orders.supplier_id', '=', 'suppliers.id')
            ->select('orders.customer_number','orders.cost','orders.date','accounts.alies','suppliers.supplier_name','accounts.internal_reporting_name','accounts.qbr','accounts.spend_name')
            ->where('orders.id','=', $id)
            ->first();
            
           return view('admin.viewdetail',compact('orders'));
        }

        if ($reportType == 'business_report') {
            return view('admin.reports.'. $reportType .'', ['pageTitle' => $setPageTitleArray[$reportType], 'accountData' => Account::select('account_name')->groupBy('account_name')->get(), 'categorySuppliers' => CategorySupplier::where('show', 0)->where('show', '!=', 1)->whereIn('id', [1,2,3,4,5])->get()]);
        } elseif ($reportType == 'commission_report') {
            return view('admin.reports.'. $reportType .'', ['pageTitle' => $setPageTitleArray[$reportType], 'categorySuppliers' => CategorySupplier::where('show', 0)->where('show', '!=', 1)->get(), 'sales_rep' => SalesTeam::select(DB::raw('CONCAT(sales_team.first_name, " ", sales_team.last_name) as sales_rep'), 'commissions.sales_rep as id')->leftJoin('commissions', 'commissions.sales_rep', '=', 'sales_team.id')->groupBy('commissions.sales_rep')->get()]);
        } elseif ($reportType == 'consolidated_report') {
            return view('admin.reports.'. $reportType .'', ['pageTitle' => $setPageTitleArray[$reportType], 'categorySuppliers' => CategorySupplier::where('show', 0)->where('show', '!=', 1)->get()]);
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
        $filter['core'] = $request->input('core');
        $filter['year'] = $request->input('year');
        $filter['supplier'] = explode(",", $request->input('supplier'));
        $filter['account_name'] = $request->input('account_name');
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

    public function  Back(){
        $url = route('report.type',['reportType' => 'business_report']);
        return redirect($url);
    }

    public function supplierReportFilter(Request $request){
        if ($request->ajax()) {
            $formatuserdata = Order::getSupplierReportFilterdData($request->all());
            return response()->json($formatuserdata);
        }
    }

    public function getCommissionsWithAjax(Request $request){
        if ($request->ajax()) {
            $formatuserdata = Order::getCommissionReportFilterdDataSecond($request->all());
            return response()->json($formatuserdata);
        }
    }

    public function supplierReportExportCsv(Request $request){
        /** Retrieve data based on the provided parameters */
        $filter['order'][0]['column'] = $request->input('column');
        $filter['order'][0]['dir'] = $request->input('order');
        $filter['start_date'] = $request->input('start_date');
        $filter['end_date'] = $request->input('end_date');
        $filter['supplier'] = $request->input('supplier');
        $filter['rebate_check'] = $request->input('rebate_check');

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
        $response->headers->set('Content-Disposition', 'attachment; filename="supplierRebateReport_'.now()->format('YmdHis').'.csv"');
  
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
        $response->headers->set('Content-Disposition', 'attachment; filename="commissionReport_'.now()->format('YmdHis').'.csv"');

        /** return $csvResponse; */
        return $response;
    }

    public function approvedUpdate(Request $request){
        if ($request->ajax()) {
            DB::table('commission_rebate')
            ->whereIn('id', $request->id)
            ->update([
                'approved' => $request->approved,
            ]);

            DB::table('commission_rebate_detail')
            ->whereIn('commission_rebate_id', $request->id)
            ->update([
                'approved' => $request->approved,
            ]);

            if ($request->approved == 1) {
                return response()->json(['success' => 'Commission approved'], 200);
            } else {
                return response()->json(['success' => 'Commission disapproved'], 200);
            }
        }
    }

    public function paidUpdate(Request $request){
        if ($request->ajax()) {
            $loggedUserId = Auth::id();
            DB::table('commission_rebate')
            ->whereIn('id', $request->id)
            ->update([
                'paid_by' => $loggedUserId,
                'paid' => $request->paid,
                'paid_date' => date('Y-m-d', strtotime($request->paid_date))
            ]);
            
            DB::table('commission_rebate_detail')
            ->whereIn('commission_rebate_id', $request->id)
            ->update([
                'paid_by' => $loggedUserId,
                'paid' => $request->paid,
                'paid_date' => date('Y-m-d', strtotime($request->paid_date))
            ]);

            if ($request->paid == 1) {
                return response()->json(['success' => 'Commission paid'], 200);
            } else {
                return response()->json(['success' => 'Commission unpaid'], 200);
            }
        }
    }

    public function downloadSampleCommissionFile(Request $request) {  
        $csv = true;
        $filter['sales_reps'] = $request->input('sales_rep');
        $filter['commission_rebate_id'] = explode(',', $request->input('commission_rebate_id'));

        $content = CommissionRebateDetailHtml::select('content')->where('commission_rebate_id', $filter['commission_rebate_id'])->first();

        if ($content) {
            $pdf = Pdf::loadHTML($content->content)->setPaper('a4', 'landscape')->setOption(['dpi' => 100, 'defaultFont' => 'mohanonda']);
            return $pdf->download('pdf_commission_report.pdf');
        } else {
            /** Fetch data using the parameters and transform it into CSV format */
            /** Replace this with your actual data fetching logic */
            $datas = Order::getCommissionReportFilterdDataSecond($filter, $csv);
            $filter['year'] = $request->input('year');
            $filter['quarter'] = $request->input('quarter');
            $allCommission = Order::getAllCommission($filter);

            $salesRep = SalesTeam::select(DB::raw('CONCAT(sales_team.first_name, " ", sales_team.last_name) as sales_rep'))->where('id', $filter['sales_reps'])->first();
            $datas1['sales_rep'] = $salesRep->sales_rep;
            $datas1['approved_by'] =   $datas[0]['approved_by'];
            $datas1['January'] = $datas1['February'] = $datas1['March'] = $datas1['April'] = $datas1['May'] = $datas1['June'] = $datas1['July'] = $datas1['August'] = $datas1['September'] = $datas1['October'] = $datas1['November'] = $datas1['December'] = 0;
            $datas1['rebate']['January'] = $datas1['rebate']['February'] = $datas1['rebate']['March'] = $datas1['rebate']['April'] = $datas1['rebate']['May'] = $datas1['rebate']['June'] = $datas1['rebate']['July'] = $datas1['rebate']['August'] = $datas1['rebate']['September'] = $datas1['rebate']['October'] = $datas1['rebate']['November'] = $datas1['rebate']['December'] = 0;

            $datas1['amount1']['January'] = $datas1['amount1']['February'] = $datas1['amount1']['March'] = $datas1['amount1']['April'] = $datas1['amount1']['May'] = $datas1['amount1']['June'] = $datas1['amount1']['July'] = $datas1['amount1']['August'] = $datas1['amount1']['September'] = $datas1['amount1']['October'] = $datas1['amount1']['November'] = $datas1['amount1']['December'] = 0;

            $datas1['quarter1'] = $datas1['quarter2'] = $datas1['quarter3'] = $datas1['quarter4'] = $datas1['anual'] = $datas1['cost'] = 0;
            $datas1['paid_check'] = $datas['paid_check'];
            unset($datas['paid_check']);
           
            foreach ($datas as $key => $data) {
                if (isset($data['quarter']) && $data['quarter'] == 1) {
                    $datas1['quarter1'] += $data['commissionss'];
                }
        
                if (isset($data['quarter']) && $data['quarter'] == 2) {
                    $datas1['quarter2'] += $data['commissionss'];
                }
        
                if (isset($data['quarter']) && $data['quarter'] == 3) {
                    $datas1['quarter3'] += $data['commissionss'];
                }
        
                if (isset($data['quarter']) && $data['quarter'] == 4) {
                    $datas1['quarter4'] += $data['commissionss'];
                }
    
                if (isset($data['commissionss'])) {
                    $datas1['anual'] += $data['commissionss'];
                }
    
                if (isset($data['cost'])) {
                    $datas1['cost'] += $data['cost'];
                }
    
                if (isset($data['month']) && $data['month'] == 'January') {
                    $datas1['January'] += $data['commissionss'];
                    $datas1['rebate']['January'] += $data['volume_rebate'];
                    $datas1['amount1']['January'] += $data['cost'];
                    $monthData[$key]['January'] = number_format($data['commissionss'], 2);
                    $monthData1[$key]['January'] = number_format($data['volume_rebate'], 2);
                    $monthData11[$key]['January'] = number_format($data['cost'], 2);
                } else {
                    $monthData[$key]['January'] = $monthData1[$key]['January'] = $monthData11[$key]['January'] = 0;
                }
                
                if (isset($data['month']) && $data['month'] == 'February') {
                    $datas1['February'] += $data['commissionss'];
                    $datas1['rebate']['February'] += $data['volume_rebate'];
                    $datas1['amount1']['February'] += $data['cost'];
                    $monthData[$key]['February'] = number_format($data['commissionss'], 2);
                    $monthData1[$key]['February'] = number_format($data['volume_rebate'], 2);
                    $monthData11[$key]['February'] = number_format($data['cost'], 2);
                } else {
                    $monthData[$key]['February'] = $monthData1[$key]['February'] = $monthData11[$key]['February'] = 0;
                }
                
                if (isset($data['month']) && $data['month'] == 'March') {
                    $datas1['March'] += $data['commissionss'];
                    $datas1['amount1']['March'] += $data['cost'];
                    $datas1['rebate']['March'] += $data['volume_rebate'];
                    $monthData[$key]['March'] = number_format($data['commissionss'], 2);
                    $monthData1[$key]['March'] = number_format($data['volume_rebate'], 2);
                    $monthData11[$key]['March'] = number_format($data['cost'], 2);
                } else {
                    $monthData[$key]['March'] = $monthData1[$key]['March'] = $monthData11[$key]['March'] = 0;
                }
                
                if (isset($data['month']) && $data['month'] == 'April') {
                    $datas1['April'] += $data['commissionss'];
                    $datas1['rebate']['April'] += $data['volume_rebate'];
                    $datas1['amount1']['March'] += $data['cost'];
                    $monthData[$key]['April'] = number_format($data['commissionss'], 2);
                    $monthData1[$key]['April'] = number_format($data['volume_rebate'], 2);
                    $monthData11[$key]['April'] = number_format($data['cost'], 2);
                } else {
                    $monthData[$key]['April'] = $monthData1[$key]['April'] = $monthData11[$key]['April'] = 0;
                }
                
                if (isset($data['month']) && $data['month'] == 'May') {
                    $datas1['May'] += $data['commissionss'];
                    $datas1['rebate']['May'] += $data['volume_rebate'];
                    $datas1['amount1']['May'] += $data['cost'];
                    $monthData[$key]['May'] = number_format($data['commissionss'], 2);
                    $monthData1[$key]['May'] = number_format($data['volume_rebate'], 2);
                    $monthData11[$key]['May'] = number_format($data['cost'], 2);
                } else {
                    $monthData[$key]['May'] = $monthData1[$key]['May'] = $monthData11[$key]['May'] = 0;
                }
                
                if (isset($data['month']) && $data['month'] == 'June') {
                    $datas1['June'] += $data['commissionss'];
                    $datas1['rebate']['June'] += $data['volume_rebate'];
                    $datas1['amount1']['June'] += $data['cost'];
                    $monthData[$key]['June'] = number_format($data['commissionss'], 2);
                    $monthData1[$key]['June'] = number_format($data['volume_rebate'], 2);
                    $monthData11[$key]['June'] = number_format($data['cost'], 2);
                } else {
                    $monthData[$key]['June'] = $monthData1[$key]['June'] = $monthData11[$key]['June'] = 0;
                }
                
                if (isset($data['month']) && $data['month'] == 'July') {
                    $datas1['July'] += $data['commissionss'];
                    $datas1['rebate']['July'] += $data['volume_rebate'];
                    $datas1['amount1']['July'] += $data['cost'];
                    $monthData[$key]['July'] = number_format($data['commissionss'], 2);
                    $monthData1[$key]['July'] = number_format($data['volume_rebate'], 2);
                    $monthData11[$key]['July'] = number_format($data['cost'], 2);
                } else {
                    $monthData[$key]['July'] = $monthData1[$key]['July'] = $monthData11[$key]['July'] = 0;
                }
                
                if (isset($data['month']) && $data['month'] == 'August') {
                    $datas1['August'] += $data['commissionss'];
                    $datas1['rebate']['August'] += $data['volume_rebate'];
                    $datas1['amount1']['August'] += $data['cost'];
                    $monthData[$key]['August'] = number_format($data['commissionss'], 2);
                    $monthData1[$key]['August'] = number_format($data['volume_rebate'], 2);
                    $monthData11[$key]['August'] = number_format($data['cost'], 2);
                } else {
                    $monthData[$key]['August'] = $monthData1[$key]['August'] = $monthData11[$key]['August'] = 0;
                }
                
                if (isset($data['month']) && $data['month'] == 'September') {
                    $datas1['September'] += $data['commissionss'];
                    $datas1['rebate']['September'] += $data['volume_rebate'];
                    $datas1['amount1']['September'] += $data['cost'];
                    $monthData[$key]['September'] = number_format($data['commissionss'], 2);
                    $monthData1[$key]['September'] = number_format($data['volume_rebate'], 2);
                    $monthData11[$key]['September'] = number_format($data['cost'], 2);
                } else {
                    $monthData[$key]['September'] = $monthData1[$key]['September'] = $monthData11[$key]['September'] = 0;
                }
                
                if (isset($data['month']) && $data['month'] == 'October') {
                    $datas1['October'] += $data['commissionss'];
                    $datas1['rebate']['October'] += $data['volume_rebate'];
                    $datas1['amount1']['October'] += $data['cost'];
                    $monthData[$key]['October'] = number_format($data['commissionss'], 2);
                    $monthData1[$key]['October'] = number_format($data['volume_rebate'], 2);
                    $monthData11[$key]['October'] = number_format($data['cost'], 2);
                } else {
                    $monthData[$key]['October'] = $monthData1[$key]['October'] = $monthData11[$key]['October'] = 0;
                }
                
                if (isset($data['month']) && $data['month'] == 'November') {
                    $datas1['November'] += $data['commissionss'];
                    $datas1['rebate']['November'] += $data['volume_rebate'];
                    $datas1['amount1']['November'] += $data['cost'];
                    $monthData[$key]['November'] = number_format($data['commissionss'], 2);
                    $monthData1[$key]['November'] = number_format($data['volume_rebate'], 2);
                    $monthData11[$key]['November'] = number_format($data['cost'], 2);
                } else {
                    $monthData[$key]['November'] = $monthData1[$key]['November'] = $monthData11[$key]['November'] = 0;
                }
                
                if (isset($data['month']) && $data['month'] == 'December') {
                    $datas1['December'] += $data['commissionss'];
                    $datas1['rebate']['December'] += $data['volume_rebate'];
                    $datas1['amount1']['December'] += $data['cost'];
                    $monthData[$key]['December'] = number_format($data['commissionss'], 2);
                    $monthData1[$key]['December'] = number_format($data['volume_rebate'], 2);
                    $monthData11[$key]['December'] = number_format($data['cost'], 2);
                } else {
                    $monthData[$key]['December'] = $monthData1[$key]['December'] = $monthData11[$key]['December'] = 0;
                }
    
                $monthData11[$key]['YTD'] = number_format($datas1['amount1']['January'] + $datas1['amount1']['February'] + $datas1['amount1']['March'] + $datas1['amount1']['April'] + $datas1['amount1']['May'] + $datas1['amount1']['June'] + $datas1['amount1']['July'] + $datas1['amount1']['August'] + $datas1['amount1']['September'] + $datas1['amount1']['October'] + $datas1['amount1']['November'] + $datas1['amount1']['December'], 2);
                $monthData1[$key]['YTD'] = number_format($datas1['rebate']['January'] + $datas1['rebate']['February'] + $datas1['rebate']['March'] + $datas1['rebate']['April'] + $datas1['rebate']['May'] + $datas1['rebate']['June'] + $datas1['rebate']['July'] + $datas1['rebate']['August'] + $datas1['rebate']['September'] + $datas1['rebate']['October'] + $datas1['rebate']['November'] + $datas1['rebate']['December'], 2);
                $monthData[$key]['YTD'] = number_format($datas1['January'] + $datas1['February'] + $datas1['March'] + $datas1['April'] + $datas1['May'] + $datas1['June'] + $datas1['July'] + $datas1['August'] + $datas1['September'] + $datas1['October'] + $datas1['November'] + $datas1['December'], 2);
                $datas1['YTD'] = number_format($datas1['January'] + $datas1['February'] + $datas1['March'] + $datas1['April'] + $datas1['May'] + $datas1['June'] + $datas1['July'] + $datas1['August'] + $datas1['September'] + $datas1['October'] + $datas1['November'] + $datas1['December'], 2); 
                $datas1['rebate']['YTD'] = number_format($datas1['rebate']['January'] + $datas1['rebate']['February'] + $datas1['rebate']['March'] + $datas1['rebate']['April'] + $datas1['rebate']['May'] + $datas1['rebate']['June'] + $datas1['rebate']['July'] + $datas1['rebate']['August'] + $datas1['rebate']['September'] + $datas1['rebate']['October'] + $datas1['rebate']['November'] + $datas1['rebate']['December'], 2); 
                $datas1['amount1']['YTD'] = number_format($datas1['amount1']['January'] + $datas1['amount1']['February'] + $datas1['amount1']['March'] + $datas1['amount1']['April'] + $datas1['amount1']['May'] + $datas1['amount1']['June'] + $datas1['amount1']['July'] + $datas1['amount1']['August'] + $datas1['amount1']['September'] + $datas1['amount1']['October'] + $datas1['amount1']['November'] + $datas1['amount1']['December'], 2); 
            }
    
            $datas1['January'] = number_format($datas1['January'], 2);
            $datas1['February'] = number_format($datas1['February'], 2);
            $datas1['March'] = number_format($datas1['March'], 2);
            $datas1['April'] = number_format($datas1['April'], 2);
            $datas1['May'] = number_format($datas1['May'], 2);
            $datas1['June'] = number_format($datas1['June'], 2);
            $datas1['July'] = number_format($datas1['July'], 2);
            $datas1['August'] = number_format($datas1['August'], 2);
            $datas1['September'] = number_format($datas1['September'], 2);
            $datas1['October'] = number_format($datas1['October'], 2);
            $datas1['November'] = number_format($datas1['November'], 2);
            $datas1['December'] = number_format($datas1['December'], 2);

            $datas1['rebate']['January'] = number_format($datas1['rebate']['January'], 2);
            $datas1['rebate']['February'] = number_format($datas1['rebate']['February'], 2);
            $datas1['rebate']['March'] = number_format($datas1['rebate']['March'], 2);
            $datas1['rebate']['April'] = number_format($datas1['rebate']['April'], 2);
            $datas1['rebate']['May'] = number_format($datas1['rebate']['May'], 2);
            $datas1['rebate']['June'] = number_format($datas1['rebate']['June'], 2);
            $datas1['rebate']['July'] = number_format($datas1['rebate']['July'], 2);
            $datas1['rebate']['August'] = number_format($datas1['rebate']['August'], 2);
            $datas1['rebate']['September'] = number_format($datas1['rebate']['September'], 2);
            $datas1['rebate']['October'] = number_format($datas1['rebate']['October'], 2);
            $datas1['rebate']['November'] = number_format($datas1['rebate']['November'], 2);
            $datas1['rebate']['December'] = number_format($datas1['rebate']['December'], 2);

            $datas1['amount1']['January'] = number_format($datas1['amount1']['January'], 2);
            $datas1['amount1']['February'] = number_format($datas1['amount1']['February'], 2);
            $datas1['amount1']['March'] = number_format($datas1['amount1']['March'], 2);
            $datas1['amount1']['April'] = number_format($datas1['amount1']['April'], 2);
            $datas1['amount1']['May'] = number_format($datas1['amount1']['May'], 2);
            $datas1['amount1']['June'] = number_format($datas1['amount1']['June'], 2);
            $datas1['amount1']['July'] = number_format($datas1['amount1']['July'], 2);
            $datas1['amount1']['August'] = number_format($datas1['amount1']['August'], 2);
            $datas1['amount1']['September'] = number_format($datas1['amount1']['September'], 2);
            $datas1['amount1']['October'] = number_format($datas1['amount1']['October'], 2);
            $datas1['amount1']['November'] = number_format($datas1['amount1']['November'], 2);
            $datas1['amount1']['December'] = number_format($datas1['amount1']['December'], 2);
                
            if ($datas1['quarter1'] != 0) {
                $datas1['quarter1'] = number_format($datas1['quarter1'], 2);
            }
    
            if ($datas1['quarter2'] != 0) {
                $datas1['quarter2'] = number_format($datas1['quarter2'], 2);
            }
    
            if ($datas1['quarter3'] != 0) {
                $datas1['quarter3'] = number_format($datas1['quarter3'], 2);
            }
    
            if ($datas1['quarter4'] != 0) {
                $datas1['quarter4'] = number_format($datas1['quarter4'], 2);
            }
            
            $datas1['anual'] = number_format($allCommission['commissionss'], 2);
            $datas1['paid_date'] = $allCommission['paid_date'];
            $datas1['month'] = $monthData;
            $datas1['month_rebate'] = $monthData1;
            $datas1['month_amount'] = $monthData11;
            $datas1['commission_data'] = $datas;

            if ($request->input('quarter') == 'Annual') {
                $datas1['quarter'] = ' '.$request->input('quarter');
            } else {
                $datas1['quarter'] = explode(' ', $request->input('quarter'))[1];
            }

            $datas1['year'] = $request->input('year');
            if ($request->input('quarter') == 'Quarter 1') {
                $datas1['commission_statement_text'] = 'January March';
            } else if ($request->input('quarter') == 'Quarter 2') {
                $datas1['commission_statement_text'] = 'April June';
            } else if ($request->input('quarter') == 'Quarter 3') {
                $datas1['commission_statement_text'] = 'July September';
            } else if ($request->input('quarter') == 'Quarter 4') {
                $datas1['commission_statement_text'] = 'October December';
            } else {
                $datas1['commission_statement_text'] = 'January December';
            }
            
            $groupedData = [];
            // echo"<pre>";
            // print_r($datas1);
            // die;
            /** Grouping the data */
            foreach ($datas1['commission_data'] as $item_key => $item) {
                $key = $item['supplier'] . '|' . $item['commissions'];
                if (!isset($groupedData[$item['account_name']][$key])) {
                    $groupedData[$item['account_name']][$key] = [
                        'account_name' => $item['account_name'],
                        'supplier' => $item['supplier'],
                        'commissions' => $item['commissions'],
                        'commission_end_date' => $item['commission_end_date'],
                        'commission_start_date' => $item['commission_start_date'],
                        'start_date' => $item['start_date'],
                        'end_date' => $item['end_date'],
                        'amounts' => [],
                        'commissionss' => [],
                        'volume_rebates' => [],
                        'month' => array_fill_keys(
                            [
                                'January',
                                'February',
                                'March',
                                'April',
                                'May',
                                'June',
                                'July',
                                'August',
                                'September',
                                'October',
                                'November',
                                'December',
                                'YTD'
                            ], 0
                        ),
                        'month_rebate' => array_fill_keys(
                            [
                                'January',
                                'February',
                                'March',
                                'April',
                                'May',
                                'June',
                                'July',
                                'August',
                                'September',
                                'October',
                                'November',
                                'December',
                                'YTD'
                            ], 0
                        ),
                        'month_amount' => array_fill_keys(
                            [
                                'January',
                                'February',
                                'March',
                                'April',
                                'May',
                                'June',
                                'July',
                                'August',
                                'September',
                                'October',
                                'November',
                                'December',
                                'YTD'
                            ], 0
                        ),
                    ];
                }

                /** Update the start_date and end_date */
                $groupedData[$item['account_name']][$key]['start_date'] = min($groupedData[$item['account_name']][$key]['start_date'], $item['start_date']);
                $groupedData[$item['account_name']][$key]['end_date'] = max($groupedData[$item['account_name']][$key]['end_date'], $item['end_date']);

                /** Append values for sum calculation */
                $groupedData[$item['account_name']][$key]['amounts'][] = $item['cost'];
                $groupedData[$item['account_name']][$key]['commissionss'][] = $item['commissionss'];
                $groupedData[$item['account_name']][$key]['volume_rebates'][] = $item['volume_rebate'];

                /** Initialize the month's array if not already set */
                if (!isset($datas1['month'][$item_key])) {
                    $datas1['month'][$item_key] = array_fill_keys(
                        [
                            'January',
                            'February',
                            'March',
                            'April',
                            'May',
                            'June',
                            'July',
                            'August',
                            'September',
                            'October',
                            'November',
                            'December',
                            'YTD'
                        ], 0
                    );
                }

                /** Initialize the month_rebate's array if not already set */
                if (!isset($datas1['month_rebate'][$item_key])) {
                    $datas1['month_rebate'][$item_key] = array_fill_keys(
                        [
                            'January',
                            'February',
                            'March',
                            'April',
                            'May',
                            'June',
                            'July',
                            'August',
                            'September',
                            'October',
                            'November',
                            'December',
                            'YTD'
                        ], 0
                    );
                }

                /** Initialize the month_amount's array if not already set */
                if (!isset($datas1['month_amount'][$item_key])) {
                    $datas1['month_amount'][$item_key] = array_fill_keys(
                        [
                            'January',
                            'February',
                            'March',
                            'April',
                            'May',
                            'June',
                            'July',
                            'August',
                            'September',
                            'October',
                            'November',
                            'December',
                            'YTD'
                        ], 0
                    );
                }

                /** Aggregate month data */
                foreach ($datas1['month'][$item_key] as $month => $value) {
                    if ($month != 'YTD') {
                        $groupedData[$item['account_name']][$key]['month'][$month] += (float)str_replace(',', '', $value);
                        $groupedData[$item['account_name']][$key]['month']['YTD'] += (float)str_replace(',', '', $value);
                    }
                }

                /** Aggregate month_rebate data */
                foreach ($datas1['month_rebate'][$item_key] as $month => $value) {
                    if ($month != 'YTD') {
                        $groupedData[$item['account_name']][$key]['month_rebate'][$month] += (float)str_replace(',', '', $value);
                        $groupedData[$item['account_name']][$key]['month_rebate']['YTD'] += (float)str_replace(',', '', $value);
                    }
                }

                /** Aggregate month_amount data */
                foreach ($datas1['month_amount'][$item_key] as $month => $value) {
                    if ($month != 'YTD') {
                        $groupedData[$item['account_name']][$key]['month_amount'][$month] += (float)str_replace(',', '', $value);
                        $groupedData[$item['account_name']][$key]['month_amount']['YTD'] += (float)str_replace(',', '', $value);
                    }
                }
            }

            /** Summarize the amounts, commissionss, and volume rebates */
            foreach ($groupedData as $key => $value) {
                foreach ($groupedData[$key] as &$data) {
                    $data['total_amount'] = array_sum($data['amounts']);
                    $data['total_commissions'] = array_sum($data['commissionss']);
                    $data['total_volume_rebate'] = array_sum($data['volume_rebates']);
                    unset($data['amounts'], $data['commissionss'], $data['volume_rebates']);
                }
            }
            
            // echo"<pre>";
            // print_r($groupedData);
            // die;

            /** Step 2: Remove the inner data array if you only need grouped data */
            $result = [];
            foreach ($groupedData as $groups) {
                foreach ($groups as $group) {
                    unset($group['data']);
                    $result[] = $group;
                }
            }

            $result1 = [];
            foreach ($result as $key => $value) {
                $result1[$value['account_name']][] = $value;
            }

            unset($datas1['month']);
            $datas1['commission_data'] = $result1;
            // echo"<pre>";
            // print_r($datas1['anual']);
            // die;
            $pdf = new Mpdf();
            $pdf->WriteHTML(view('admin.pdf.commission_pdf', $datas1));
            return $pdf->Output('pdf_commission_report.pdf', 'I');
            // return $pdf->Output('pdf_commission_report.pdf', 'D');
        }
    }

    public function consolidatedReportFilter(Request $request) {
        if ($request->ajax()) {
            $formatuserdata = Order::getConsolidatedReportFilterdData($request->all());
            return response()->json($formatuserdata);
        }
    }

    public function exportConsolidatedCsv(Request $request){
        /** Retrieve data based on the provided parameters */
        $filter['order'][0]['column'] = $request->input('column');
        $filter['order'][0]['dir'] = $request->input('order');
        $filter['end_date'] = $request->input('end_date');
        $filter['start_date'] = $request->input('start_date');
        $filter['account_name'] = $request->input('account_name');
        $filter['supplier_id'] = explode(",", $request->input('supplier_id'));        
        
        /** Fetch data using the parameters and transform it into CSV format */
        /** Replace this with your actual data fetching logic */
        $data = Order::getConsolidatedReportFilterdData($filter, true);

        /** Create a stream for output */
        $stream = fopen('php://temp', 'w+');

        /** Create a new CSV writer instance */
        $csvWriter = Writer::createFromStream($stream);
        
        $heading = $data['heading'];
        unset($data['heading']);

        /** Add column headings */
        $csvWriter->insertOne($heading);

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
        $response->headers->set('Content-Disposition', 'attachment; filename="Consolidated_Report_'.now()->format('YmdHis').'.csv"');
    
        /** return $csvResponse; */
        return $response;
    }

    public function exportConsolidatedDownload(Request $request) {
        /** Fetch data using the parameters and transform it into CSV format */
        $filter = [
            'account_name' => $request->input('account_name'),
            'supplier_id' => $request->input('supplier_id'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date')
        ];

        /** Increasing the memory limit becouse memory limit issue */
        ini_set('memory_limit', '1024M');

        $data = Order::getConsolidatedDownloadData($filter);

        /** Create a stream for output */
        $stream = fopen('php://temp', 'w+');

        /** Create a new CSV writer instance */
        $csvWriter = Writer::createFromStream($stream);
        
        $previousKeys = [];

        /** Loop through data */
        foreach ($data as $row) {
            $currentKeys = array_keys($row);

            /** Check if the keys have changed */
            if ($currentKeys !== $previousKeys) {
                /** If keys have changed, insert the new heading row */
                $csvWriter->insertOne($currentKeys);
                $previousKeys = $currentKeys;
            }

            /** Reorder the current row according to the current keys */
            $orderedRow = [];
            foreach ($currentKeys as $key) {
                $orderedRow[] = $row[$key] ?? '';
            }

            /** Insert the data row */
            $csvWriter->insertOne($orderedRow);
        }

        /** Rewind the stream pointer */
        rewind($stream);

        /** Create a streamed response with the CSV data */
        $response = new StreamedResponse(function () use ($stream) {
            fpassthru($stream);
        });

        /** Set headers for CSV download */
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="Consolidated_Report_'.now()->format('YmdHis').'.csv"');
    
        /** return $csvResponse; */
        return $response;
    }

    public function operationalAnomalyReportFilter(Request $request) {
        if ($request->ajax()) {
            $formatuserdata = Order::operationalAnomalyReportFilterdData($request->all());
            return response()->json($formatuserdata);
        }
    }

    public function operationalAnomalyReportExportCsv(Request $request){
        /** Retrieve data based on the provided parameters */
        $filter['date'] = $request->input('date');
        $filter['supplier'] = $request->input('supplier');
        $filter['supplier_id'] = $request->input('supplier_id');

        $csv = true;

        /** Fetch data using the parameters and transform it into CSV format */
        /** Replace this with your actual data fetching logic */
        $data = Order::operationalAnomalyReportFilterdData($filter, $csv);

        /** Create a stream for output */
        $stream = fopen('php://temp', 'w+');

        /** Create a new CSV writer instance */
        $csvWriter = Writer::createFromStream($stream);
        
        $heading = $data['heading'];
        unset($data['heading']);

        /** Add column headings */
        $csvWriter->insertOne($heading);

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
        $response->headers->set('Content-Disposition', 'attachment; filename="operationalAnomalyReport_'.now()->format('YmdHis').'.csv"');

        /** return $csvResponse; */
        return $response;
    }

}
