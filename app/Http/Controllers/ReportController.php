<?php

namespace App\Http\Controllers;

use League\Csv\Writer;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\{Order, CategorySupplier, Account, SalesTeam, CommissionRebate, CommissionRebateDetailHtml};


class ReportController extends Controller
{
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
            return view('admin.reports.'. $reportType .'', ['pageTitle' => $setPageTitleArray[$reportType], 'accountData' => Account::select('account_name')->groupBy('account_name')->get(), 'categorySuppliers' => CategorySupplier::where('show', 0)->where('show', '!=', 1)->whereIn('id', [1,2,3,4,5])->get()]);
        } elseif ($reportType == 'commission_report') {
            return view('admin.reports.'. $reportType .'', ['pageTitle' => $setPageTitleArray[$reportType], 'categorySuppliers' => CategorySupplier::where('show', 0)->where('show', '!=', 1)->get(), 'sales_rep' => SalesTeam::select(DB::raw('CONCAT(sales_team.first_name, " ", sales_team.last_name) as sales_rep'), 'commission.sales_rep as id')->leftJoin('commission', 'commission.sales_rep', '=', 'sales_team.id')->groupBy('commission.sales_rep')->get()]);
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
            DB::table('commission_rebate')->whereIn('id', $request->id)
            ->update([
                'approved' => $request->approved
            ]);

            DB::table('commission_rebate_detail')->whereIn('commission_rebate_id', $request->id)
            ->update([
                'approved' => $request->approved
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
            // $commissionRebate = CommissionRebate::find($request->id); 

            // dd($request->paid_date);
            DB::table('commission_rebate')->whereIn('id', $request->id)
                ->update([
                'paid' => $request->paid,
                'paid_date' => date('Y-m-d', strtotime($request->paid_date))
            ]);
            
            DB::table('commission_rebate_detail')->whereIn('commission_rebate_id', $request->id)
                ->update([
                'paid' => $request->paid,
                'paid_date' => date('Y-m-d', strtotime($request->paid_date))
            ]);

            // if ($commissionRebate->paid == 1 && $commissionRebate->approved == 1) {
            //     $csv = true;
            //     $filter['sales_rep'] = $commissionRebate->sales_rep;
            //     $filter['commission_rebate_id'] = $commissionRebate->id;

            //     /** Fetch data using the parameters and transform it into CSV format */
            //     /** Replace this with your actual data fetching logic */
            //     $datas = Order::getCommissionReportFilterdDataSecond($filter, $csv);
        
            //     $salesRep = SalesTeam::select(DB::raw('CONCAT(sales_team.first_name, " ", sales_team.last_name) as sales_rep'))->where('id', $filter['sales_rep'])->first();
            //     $datas1['sales_rep'] = $salesRep->sales_rep;
        
            //     $datas1['January'] = $datas1['February'] = $datas1['March'] = $datas1['April'] = $datas1['May'] = $datas1['June'] = $datas1['July'] = $datas1['August'] = $datas1['September'] = $datas1['October'] = $datas1['November'] = $datas1['December'] = 0;
            //     $datas1['quarter1'] = $datas1['quarter2'] = $datas1['quarter3'] = $datas1['quarter4'] = $datas1['anual'] = $datas1['amount'] = 0;
            //     $datas1['paid_check'] = $datas['paid_check'];
            //     unset($datas['paid_check']);

            //     foreach ($datas as $key => $data) {
            //         if (isset($data['quarter']) && $data['quarter'] == 1) {
            //             $datas1['quarter1'] += $data['commissions'];
            //         }
            
            //         if (isset($data['quarter']) && $data['quarter'] == 2) {
            //             $datas1['quarter2'] += $data['commissions'];
            //         }
            
            //         if (isset($data['quarter']) && $data['quarter'] == 3) {
            //             $datas1['quarter3'] += $data['commissions'];
            //         }
            
            //         if (isset($data['quarter']) && $data['quarter'] == 4) {
            //             $datas1['quarter4'] += $data['commissions'];
            //         }
        
            //         if (isset($data['commissions'])) {
            //             $datas1['anual'] += $data['commissions'];
            //         }
        
            //         if (isset($data['amount'])) {
            //             $datas1['amount'] += $data['amount'];
            //         }
        
            //         if (isset($data['month']) && $data['month'] == 'January') {
            //             $datas1['January'] += number_format($data['commissions'], 2);
            //             $monthData[$key]['January'] = number_format($data['commissions'], 2);
            //         } else {
            //             $monthData[$key]['January'] = 0;
            //         }
                    
            //         if (isset($data['month']) && $data['month'] == 'February') {
            //             $datas1['February'] += number_format($data['commissions'], 2);
            //             $monthData[$key]['February'] = number_format($data['commissions'], 2);
            //         } else {
            //             $monthData[$key]['February'] = 0;
            //         }
                    
            //         if (isset($data['month']) && $data['month'] == 'March') {
            //             $datas1['March'] += number_format($data['commissions'], 2);
            //             $monthData[$key]['March'] = number_format($data['commissions'], 2);
            //         } else {
            //             $monthData[$key]['March'] = 0;
            //         }
                    
            //         if (isset($data['month']) && $data['month'] == 'April') {
            //             $datas1['April'] += number_format($data['commissions'], 2);
            //             $monthData[$key]['April'] = number_format($data['commissions'], 2);
            //         } else {
            //             $monthData[$key]['April'] = 0;
            //         }
                    
            //         if (isset($data['month']) && $data['month'] == 'May') {
            //             $datas1['May'] += number_format($data['commissions'], 2);
            //             $monthData[$key]['May'] = number_format($data['commissions'], 2);
            //         } else {
            //             $monthData[$key]['May'] = 0;
            //         }
                    
            //         if (isset($data['month']) && $data['month'] == 'June') {
            //             $datas1['June'] += number_format($data['commissions'], 2);
            //             $monthData[$key]['June'] = number_format($data['commissions'], 2);
            //         } else {
            //             $monthData[$key]['June'] = 0;
            //         }
                    
            //         if (isset($data['month']) && $data['month'] == 'July') {
            //             $datas1['July'] += number_format($data['commissions'], 2);
            //             $monthData[$key]['July'] = number_format($data['commissions'], 2);
            //         } else {
            //             $monthData[$key]['July'] = 0;
            //         }
                    
            //         if (isset($data['month']) && $data['month'] == 'August') {
            //             $datas1['August'] += number_format($data['commissions'], 2);
            //             $monthData[$key]['August'] = number_format($data['commissions'], 2);
            //         } else {
            //             $monthData[$key]['August'] = 0;
            //         }
                    
            //         if (isset($data['month']) && $data['month'] == 'September') {
            //             $datas1['September'] += number_format($data['commissions'], 2);
            //             $monthData[$key]['September'] = number_format($data['commissions'], 2);
            //         } else {
            //             $monthData[$key]['September'] = 0;
            //         }
                    
            //         if (isset($data['month']) && $data['month'] == 'October') {
            //             $datas1['October'] += number_format($data['commissions'], 2);
            //             $monthData[$key]['October'] = number_format($data['commissions'], 2);
            //         } else {
            //             $monthData[$key]['October'] = 0;
            //         }
                    
            //         if (isset($data['month']) && $data['month'] == 'November') {
            //             $datas1['November'] += number_format($data['commissions'], 2);
            //             $monthData[$key]['November'] = number_format($data['commissions'], 2);
            //         } else {
            //             $monthData[$key]['November'] = 0;
            //         }
                    
            //         if (isset($data['month']) && $data['month'] == 'December') {
            //             $datas1['December'] += number_format($data['commissions'], 2);
            //             $monthData[$key]['December'] = number_format($data['commissions'], 2);
            //         } else {
            //             $monthData[$key]['December'] = 0;
            //         }
        
            //         $monthData[$key]['YTD'] = number_format($monthData[$key]['January'] + $monthData[$key]['February'] + $monthData[$key]['March'] + $monthData[$key]['April'] + $monthData[$key]['May'] + $monthData[$key]['June'] + $monthData[$key]['July'] + $monthData[$key]['August'] + $monthData[$key]['September'] + $monthData[$key]['October'] + $monthData[$key]['November'] + $monthData[$key]['December'] , 2);
            //         $datas1['YTD'] = number_format($datas1['January'] + $datas1['February'] + $datas1['March'] + $datas1['April'] + $datas1['May'] + $datas1['June'] + $datas1['July'] + $datas1['August'] + $datas1['September'] + $datas1['October'] + $datas1['November'] + $datas1['December'], 2);
            //     }
        
            //     if ($datas1['quarter1'] != 0) {
            //         $datas1['quarter1'] = number_format($datas1['quarter1'], 2);
            //     }
        
            //     if ($datas1['quarter2'] != 0) {
            //         $datas1['quarter2'] = number_format($datas1['quarter2'], 2);
            //     }
        
            //     if ($datas1['quarter3'] != 0) {
            //         $datas1['quarter3'] = number_format($datas1['quarter3'], 2);
            //     }
        
            //     if ($datas1['quarter4'] != 0) {
            //         $datas1['quarter4'] = number_format($datas1['quarter4'], 2);
            //     }
                
            //     $datas1['month'] = $monthData;
            //     $datas1['commission_data'] = $datas;
            //     $datas1['anual'] = number_format($datas1['anual'], 2);
            //     $view = View::make('admin.pdf.commission_pdf', $datas1);

            //     CommissionRebateDetailHtml::create([
            //         'paid' => 1,
            //         'approved' => 1,
            //         'content' => $view,
            //         'month' => $datas[0]['month'],
            //         'sales_rep' => $filter['sales_rep'],
            //         'spend' => $commissionRebate->spend,
            //         'commission' => $commissionRebate->commission,
            //         'commission_rebate_id' => $commissionRebate->id,
            //         'volume_rebate' => $commissionRebate->volume_rebate,
            //     ]);
            // }

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
    
            $datas1['January'] = $datas1['February'] = $datas1['March'] = $datas1['April'] = $datas1['May'] = $datas1['June'] = $datas1['July'] = $datas1['August'] = $datas1['September'] = $datas1['October'] = $datas1['November'] = $datas1['December'] = 0;
            $datas1['rebate']['January'] = $datas1['rebate']['February'] = $datas1['rebate']['March'] = $datas1['rebate']['April'] = $datas1['rebate']['May'] = $datas1['rebate']['June'] = $datas1['rebate']['July'] = $datas1['rebate']['August'] = $datas1['rebate']['September'] = $datas1['rebate']['October'] = $datas1['rebate']['November'] = $datas1['rebate']['December'] = 0;

            $datas1['amount1']['January'] = $datas1['amount1']['February'] = $datas1['amount1']['March'] = $datas1['amount1']['April'] = $datas1['amount1']['May'] = $datas1['amount1']['June'] = $datas1['amount1']['July'] = $datas1['amount1']['August'] = $datas1['amount1']['September'] = $datas1['amount1']['October'] = $datas1['amount1']['November'] = $datas1['amount1']['December'] = 0;

            $datas1['quarter1'] = $datas1['quarter2'] = $datas1['quarter3'] = $datas1['quarter4'] = $datas1['anual'] = $datas1['amount'] = 0;
            $datas1['paid_check'] = $datas['paid_check'];
            unset($datas['paid_check']);

            foreach ($datas as $key => $data) {
                if (isset($data['quarter']) && $data['quarter'] == 1) {
                    $datas1['quarter1'] += $data['commissions'];
                }
        
                if (isset($data['quarter']) && $data['quarter'] == 2) {
                    $datas1['quarter2'] += $data['commissions'];
                }
        
                if (isset($data['quarter']) && $data['quarter'] == 3) {
                    $datas1['quarter3'] += $data['commissions'];
                }
        
                if (isset($data['quarter']) && $data['quarter'] == 4) {
                    $datas1['quarter4'] += $data['commissions'];
                }
    
                if (isset($data['commissions'])) {
                    $datas1['anual'] += $data['commissions'];
                }
    
                if (isset($data['amount'])) {
                    $datas1['amount'] += $data['amount'];
                }
    
                if (isset($data['month']) && $data['month'] == 'January') {
                    $datas1['January'] += $data['commissions'];
                    $datas1['rebate']['January'] += $data['volume_rebate'];
                    $datas1['amount1']['January'] += $data['amount'];
                    $monthData[$key]['January'] = number_format($data['commissions'], 2);
                    $monthData1[$key]['January'] = number_format($data['volume_rebate'], 2);
                    $monthData11[$key]['January'] = number_format($data['amount'], 2);
                } else {
                    $monthData[$key]['January'] = 0;
                    $monthData1[$key]['January'] = 0;
                    $monthData11[$key]['January'] = 0;
                }
                
                if (isset($data['month']) && $data['month'] == 'February') {
                    $datas1['February'] += $data['commissions'];
                    $datas1['rebate']['February'] += $data['volume_rebate'];
                    $datas1['amount1']['February'] += $data['amount'];
                    $monthData[$key]['February'] = number_format($data['commissions'], 2);
                    $monthData1[$key]['February'] = number_format($data['volume_rebate'], 2);
                    $monthData11[$key]['February'] = number_format($data['amount'], 2);
                } else {
                    $monthData[$key]['February'] = 0;
                    $monthData1[$key]['February'] = 0;
                    $monthData11[$key]['February'] = 0;
                }
                
                if (isset($data['month']) && $data['month'] == 'March') {
                    $datas1['March'] += $data['commissions'];
                    $datas1['amount1']['March'] += $data['amount'];
                    $datas1['rebate']['March'] += $data['volume_rebate'];
                    $monthData[$key]['March'] = number_format($data['commissions'], 2);
                    $monthData1[$key]['March'] = number_format($data['volume_rebate'], 2);
                    $monthData11[$key]['March'] = number_format($data['amount'], 2);
                } else {
                    $monthData[$key]['March'] = 0;
                    $monthData1[$key]['March'] = 0;
                    $monthData11[$key]['March'] = 0;
                }
                
                if (isset($data['month']) && $data['month'] == 'April') {
                    $datas1['April'] += $data['commissions'];
                    $datas1['rebate']['April'] += $data['volume_rebate'];
                    $datas1['amount1']['March'] += $data['amount'];
                    $monthData[$key]['April'] = number_format($data['commissions'], 2);
                    $monthData1[$key]['April'] = number_format($data['volume_rebate'], 2);
                    $monthData11[$key]['April'] = number_format($data['amount'], 2);
                } else {
                    $monthData[$key]['April'] = 0;
                    $monthData1[$key]['April'] = 0;
                    $monthData11[$key]['April'] = 0;
                }
                
                if (isset($data['month']) && $data['month'] == 'May') {
                    $datas1['May'] += $data['commissions'];
                    $datas1['rebate']['May'] += $data['volume_rebate'];
                    $datas1['amount1']['May'] += $data['amount'];
                    $monthData[$key]['May'] = number_format($data['commissions'], 2);
                    $monthData1[$key]['May'] = number_format($data['volume_rebate'], 2);
                    $monthData11[$key]['May'] = number_format($data['amount'], 2);
                } else {
                    $monthData[$key]['May'] = 0;
                    $monthData1[$key]['May'] = 0;
                    $monthData11[$key]['May'] = 0;
                }
                
                if (isset($data['month']) && $data['month'] == 'June') {
                    $datas1['June'] += $data['commissions'];
                    $datas1['rebate']['June'] += $data['volume_rebate'];
                    $datas1['amount1']['June'] += $data['amount'];
                    $monthData[$key]['June'] = number_format($data['commissions'], 2);
                    $monthData1[$key]['June'] = number_format($data['volume_rebate'], 2);
                    $monthData11[$key]['June'] = number_format($data['amount'], 2);
                } else {
                    $monthData[$key]['June'] = 0;
                    $monthData1[$key]['June'] = 0;
                    $monthData11[$key]['June'] = 0;
                }
                
                if (isset($data['month']) && $data['month'] == 'July') {
                    $datas1['July'] += $data['commissions'];
                    $datas1['rebate']['July'] += $data['volume_rebate'];
                    $datas1['amount1']['July'] += $data['amount'];
                    $monthData[$key]['July'] = number_format($data['commissions'], 2);
                    $monthData1[$key]['July'] = number_format($data['volume_rebate'], 2);
                    $monthData11[$key]['July'] = number_format($data['amount'], 2);
                } else {
                    $monthData[$key]['July'] = 0;
                    $monthData1[$key]['July'] = 0;
                    $monthData11[$key]['July'] = 0;
                }
                
                if (isset($data['month']) && $data['month'] == 'August') {
                    $datas1['August'] += $data['commissions'];
                    $datas1['rebate']['August'] += $data['volume_rebate'];
                    $datas1['amount1']['August'] += $data['amount'];
                    $monthData[$key]['August'] = number_format($data['commissions'], 2);
                    $monthData1[$key]['August'] = number_format($data['volume_rebate'], 2);
                    $monthData11[$key]['August'] = number_format($data['amount'], 2);
                } else {
                    $monthData[$key]['August'] = 0;
                    $monthData1[$key]['August'] = 0;
                    $monthData11[$key]['August'] = 0;
                }
                
                if (isset($data['month']) && $data['month'] == 'September') {
                    $datas1['September'] += $data['commissions'];
                    $datas1['rebate']['September'] += $data['volume_rebate'];
                    $datas1['amount1']['September'] += $data['amount'];
                    $monthData[$key]['September'] = number_format($data['commissions'], 2);
                    $monthData1[$key]['September'] = number_format($data['volume_rebate'], 2);
                    $monthData11[$key]['September'] = number_format($data['amount'], 2);
                } else {
                    $monthData[$key]['September'] = 0;
                    $monthData1[$key]['September'] = 0;
                    $monthData11[$key]['September'] = 0;
                }
                
                if (isset($data['month']) && $data['month'] == 'October') {
                    $datas1['October'] += $data['commissions'];
                    $datas1['rebate']['October'] += $data['volume_rebate'];
                    $datas1['amount1']['October'] += $data['amount'];
                    $monthData[$key]['October'] = number_format($data['commissions'], 2);
                    $monthData1[$key]['October'] = number_format($data['volume_rebate'], 2);
                    $monthData11[$key]['October'] = number_format($data['amount'], 2);
                } else {
                    $monthData[$key]['October'] = 0;
                    $monthData1[$key]['October'] = 0;
                    $monthData11[$key]['October'] = 0;
                }
                
                if (isset($data['month']) && $data['month'] == 'November') {
                    $datas1['November'] += $data['commissions'];
                    $datas1['rebate']['November'] += $data['volume_rebate'];
                    $datas1['amount1']['November'] += $data['amount'];
                    $monthData[$key]['November'] = number_format($data['commissions'], 2);
                    $monthData1[$key]['November'] = number_format($data['volume_rebate'], 2);
                    $monthData11[$key]['November'] = number_format($data['amount'], 2);
                } else {
                    $monthData[$key]['November'] = 0;
                    $monthData1[$key]['November'] = 0;
                    $monthData11[$key]['November'] = 0;
                }
                
                if (isset($data['month']) && $data['month'] == 'December') {
                    $datas1['December'] += $data['commissions'];
                    $datas1['rebate']['December'] += $data['volume_rebate'];
                    $datas1['amount1']['December'] += $data['amount'];
                    $monthData[$key]['December'] = number_format($data['commissions'], 2);
                    $monthData1[$key]['December'] = number_format($data['volume_rebate'], 2);
                    $monthData11[$key]['December'] = number_format($data['amount'], 2);
                } else {
                    $monthData[$key]['December'] = 0;
                    $monthData1[$key]['December'] = 0;
                    $monthData11[$key]['December'] = 0;
                }
    
                $monthData11[$key]['YTD'] = number_format($datas1['amount1']['January'] + $datas1['amount1']['February'] + $datas1['amount1']['March'] + $datas1['amount1']['April'] + $datas1['amount1']['May'] + $datas1['amount1']['June'] + $datas1['amount1']['July'] + $datas1['amount1']['August'] + $datas1['amount1']['September'] + $datas1['amount1']['October'] + $datas1['amount1']['November'] + $datas1['amount1']['December'], 2);
                $monthData1[$key]['YTD'] = number_format($datas1['rebate']['January'] + $datas1['rebate']['February'] + $datas1['rebate']['March'] + $datas1['rebate']['April'] + $datas1['rebate']['May'] + $datas1['rebate']['June'] + $datas1['rebate']['July'] + $datas1['rebate']['August'] + $datas1['rebate']['September'] + $datas1['rebate']['October'] + $datas1['rebate']['November'] + $datas1['rebate']['December'], 2);
                $monthData[$key]['YTD'] = number_format($datas1['January'] + $datas1['February'] + $datas1['March'] + $datas1['April'] + $datas1['May'] + $datas1['June'] + $datas1['July'] + $datas1['August'] + $datas1['September'] + $datas1['October'] + $datas1['November'] + $datas1['December'], 2);
                // number_format($monthData[$key]['January'] + $monthData[$key]['February'] + $monthData[$key]['March'] + $monthData[$key]['April'] + $monthData[$key]['May'] + $monthData[$key]['June'] + $monthData[$key]['July'] + $monthData[$key]['August'] + $monthData[$key]['September'] + $monthData[$key]['October'] + $monthData[$key]['November'] + $monthData[$key]['December'] , 2);
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
            
            $datas1['anual'] = number_format($allCommission['commissions'], 2);
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
            if ($request->input('quarter') == 1) {
                $datas1['commission_statement_text'] = 'January March';
            } else if ($request->input('quarter') == 2) {
                $datas1['commission_statement_text'] = 'April June';
            } else if ($request->input('quarter') == 3) {
                $datas1['commission_statement_text'] = 'July September';
            } else if ($request->input('quarter') == 4) {
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
                $key = $item['supplier'] . '|' . $item['commission'];
                if (!isset($groupedData[$item['account_name']][$key])) {
                    $groupedData[$item['account_name']][$key] = [
                        'account_name' => $item['account_name'],
                        'supplier' => $item['supplier'],
                        'commission' => $item['commission'],
                        'start_date' => $item['start_date'],
                        'end_date' => $item['end_date'],
                        'amounts' => [],
                        'commissions' => [],
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
                $groupedData[$item['account_name']][$key]['amounts'][] = $item['amount'];
                $groupedData[$item['account_name']][$key]['commissions'][] = $item['commissions'];
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

            /** Summarize the amounts, commissions, and volume rebates */
            foreach ($groupedData as $key => $value) {
                foreach ($groupedData[$key] as &$data) {
                    $data['total_amount'] = array_sum($data['amounts']);
                    $data['total_commissions'] = array_sum($data['commissions']);
                    $data['total_volume_rebate'] = array_sum($data['volume_rebates']);
                    unset($data['amounts'], $data['commissions'], $data['volume_rebates']);
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
            // print_r($datas1['commission_data']);
            // die;
            /** Output the result */
            $pdf = Pdf::loadView('admin.pdf.commission_pdf', $datas1)->setOption('dpi', 80)
            ->setOption('defaultFont', 'mohanonda')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isPhpEnabled', true)
            ->setOption('isRemoteEnabled', true)
            ->setOption('pageSize', 'A4')
            ->setOption('margin-top', '10mm')
            ->setOption('margin-bottom', '10mm')
            ->setOption('margin-left', '10mm')
            ->setOption('margin-right', '10mm');
            return $pdf->download('pdf_commission_report.pdf');
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
}
