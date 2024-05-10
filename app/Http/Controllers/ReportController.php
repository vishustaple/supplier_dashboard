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
                    $monthData[$key]['January'] = number_format($data['commissions'], 2);
                } else {
                    $monthData[$key]['January'] = 0;
                }
                
                if (isset($data['month']) && $data['month'] == 'February') {
                    $datas1['February'] += $data['commissions'];
                    $monthData[$key]['February'] = number_format($data['commissions'], 2);
                } else {
                    $monthData[$key]['February'] = 0;
                }
                
                if (isset($data['month']) && $data['month'] == 'March') {
                    $datas1['March'] += $data['commissions'];
                    $monthData[$key]['March'] = number_format($data['commissions'], 2);
                } else {
                    $monthData[$key]['March'] = 0;
                }
                
                if (isset($data['month']) && $data['month'] == 'April') {
                    $datas1['April'] += $data['commissions'];
                    $monthData[$key]['April'] = number_format($data['commissions'], 2);
                } else {
                    $monthData[$key]['April'] = 0;
                }
                
                if (isset($data['month']) && $data['month'] == 'May') {
                    $datas1['May'] += $data['commissions'];
                    $monthData[$key]['May'] = number_format($data['commissions'], 2);
                } else {
                    $monthData[$key]['May'] = 0;
                }
                
                if (isset($data['month']) && $data['month'] == 'June') {
                    $datas1['June'] += $data['commissions'];
                    $monthData[$key]['June'] = number_format($data['commissions'], 2);
                } else {
                    $monthData[$key]['June'] = 0;
                }
                
                if (isset($data['month']) && $data['month'] == 'July') {
                    $datas1['July'] += $data['commissions'];
                    $monthData[$key]['July'] = number_format($data['commissions'], 2);
                } else {
                    $monthData[$key]['July'] = 0;
                }
                
                if (isset($data['month']) && $data['month'] == 'August') {
                    $datas1['August'] += $data['commissions'];
                    $monthData[$key]['August'] = number_format($data['commissions'], 2);
                } else {
                    $monthData[$key]['August'] = 0;
                }
                
                if (isset($data['month']) && $data['month'] == 'September') {
                    $datas1['September'] += $data['commissions'];
                    $monthData[$key]['September'] = number_format($data['commissions'], 2);
                } else {
                    $monthData[$key]['September'] = 0;
                }
                
                if (isset($data['month']) && $data['month'] == 'October') {
                    $datas1['October'] += $data['commissions'];
                    $monthData[$key]['October'] = number_format($data['commissions'], 2);
                } else {
                    $monthData[$key]['October'] = 0;
                }
                
                if (isset($data['month']) && $data['month'] == 'November') {
                    $datas1['November'] += $data['commissions'];
                    $monthData[$key]['November'] = number_format($data['commissions'], 2);
                } else {
                    $monthData[$key]['November'] = 0;
                }
                
                if (isset($data['month']) && $data['month'] == 'December') {
                    $datas1['December'] += $data['commissions'];
                    $monthData[$key]['December'] = number_format($data['commissions'], 2);
                } else {
                    $monthData[$key]['December'] = 0;
                }
    
                $monthData[$key]['YTD'] = number_format($datas1['January'] + $datas1['February'] + $datas1['March'] + $datas1['April'] + $datas1['May'] + $datas1['June'] + $datas1['July'] + $datas1['August'] + $datas1['September'] + $datas1['October'] + $datas1['November'] + $datas1['December'], 2);
                // number_format($monthData[$key]['January'] + $monthData[$key]['February'] + $monthData[$key]['March'] + $monthData[$key]['April'] + $monthData[$key]['May'] + $monthData[$key]['June'] + $monthData[$key]['July'] + $monthData[$key]['August'] + $monthData[$key]['September'] + $monthData[$key]['October'] + $monthData[$key]['November'] + $monthData[$key]['December'] , 2);
                $datas1['YTD'] = number_format($datas1['January'] + $datas1['February'] + $datas1['March'] + $datas1['April'] + $datas1['May'] + $datas1['June'] + $datas1['July'] + $datas1['August'] + $datas1['September'] + $datas1['October'] + $datas1['November'] + $datas1['December'], 2);

                
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
            $datas1['commission_data'] = $datas;
            $datas1['quarter'] = explode(' ', $request->input('quarter'))[1];
            $datas1['year'] = $request->input('year');                       

            $pdf = Pdf::loadView('admin.pdf.commission_pdf', $datas1)->setPaper('a4', 'landscape')->setOption(['dpi' => 100, 'defaultFont' => 'mohanonda']);
            // print_r($pdf);
            // die;
            // return $pdf;
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
