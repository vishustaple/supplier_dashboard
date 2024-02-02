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
            // $data = User::latest()->get();
            // return Datatables::of($data)
            //     ->addIndexColumn()
            //     ->addColumn('action', function ($row) {
            //         $btn = '<button type="button" class="btn btn-primary">Action</button>';
            //         return $btn;
            //     })
            //     ->rawColumns(['action'])
            //     ->make(true);
            // echo"<pre>";
            //     print_r($request->all());
// die;
        $filter = $request->all();

        $orderColumnArray = [
        0 => 'orders.id',
        1 => 'orders.amount',
        2 => 'orders.date',
        3 => 'suppliers.supplier_name',
        4 => 'orders.customer_number',
        5 => "accounts.customer_name"
        ];


        $query = Order::query() // Replace YourModel with the actual model you are using for the data
        ->join('suppliers', 'orders.supplier_id', '=', 'suppliers.id')
        ->join('accounts', 'orders.customer_number', '=', 'accounts.customer_number')
        ->select('orders.id as id', 'orders.amount as amount', 'orders.date as date', 'suppliers.supplier_name as supplier_name', 'orders.customer_number as customer_number', "accounts.customer_name as customer_name"); // Adjust the column names as needed
        // Filter data based on request parameters
        if ($request->filled('start_date')) {
            $query->whereDate('orders.date', '>=', $filter['start_date']);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('orders.date', '<=', $request->input('end_date'));
        }

        if ($request->filled('supplierId')) {
            $query->where('orders.supplier_id', $request->input('supplierId'));
        }

        // // Order by column and direction
        $query->orderBy($orderColumnArray[$filter['order'][0]['column']], $filter['order'][0]['dir']);

        // // Get paginated results based on start, length
        $filteredData = $query->skip($filter['start'])->take($filter['length'])->get();

        // $filterdData = $query->get();

        $formatuserdata=[];
        foreach ($filteredData as $key => $data) {
            $formatuserdata[$key]['id'] = $data->id;
            $formatuserdata[$key]['customer_number'] = $data->customer_number;
            $formatuserdata[$key]['customer_name'] = $data->customer_name;
            $formatuserdata[$key]['supplier_name'] = $data->supplier_name;
            $formatuserdata[$key]['amount'] = $data->amount;
            $formatuserdata[$key]['date'] = $data->date;
        }
        return response()->json(['data' =>$formatuserdata]);
        // Use DataTables to handle the server-side processing
        // return DataTables::of($query)
        //     ->addIndexColumn()
        //     ->addColumn('customer_number', function ($row) {
        //         return $row->customer_number;
        //     })
        //     ->addColumn('customer_name', function ($row) {
        //         return $row->customer_name;
        //     })
        //     ->addColumn('amount', function ($row) {
        //         return $row->amount;
        //     })
        //     ->addColumn('supplier_name', function ($row) {
        //         return $row->supplier_name;
        //     })
        //     ->addColumn('date', function ($row) {
        //         return $row->date;
        //     })
        //     ->make(true);
        }
    }
}
