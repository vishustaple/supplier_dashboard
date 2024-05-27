<?php

namespace App\Http\Controllers;

use League\Csv\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\{Account, Commission};
use Symfony\Component\HttpFoundation\StreamedResponse;


class CommissionController extends Controller
{
    public function index(Request $request, $commissionType, $id=null){
        if (!isset($id)) {
            $id = $request->query('id');
        }

        $setPageTitleArray = [
            'commission_listing' => 'Commission',
        ];

        if (isset($id)) {
            $catalog = Account::query() 
            ->leftJoin('suppliers', 'catalog.supplier_id', '=', 'suppliers.id')
            ->leftJoin('catalog_details', 'catalog.id', '=', 'catalog_details.catalog_id')
            ->where('catalog.id', '=', $id)
            ->whereNotNull('catalog_details.table_value')
            ->select('catalog_details.table_key as key', 'catalog_details.table_value as value', 'catalog.sku as sku','catalog.price as price','suppliers.supplier_name as supplier_name','catalog.description as description')->get()->toArray();
            return view('admin.viewdetail',compact('catalog'));
        }

        return view('admin.commission.'. $commissionType .'', ['pageTitle' => $setPageTitleArray[$commissionType]]);
    }

    public function commissionAjaxCustomerSearch(Request $request){
        if ($request->ajax()) {
            $formatuserdata = Account::getSearchCustomerData($request->input('q'), $request->input('supplier'), $request->input('supplier_array'), $request->input('supplier_check'));
            return response()->json($formatuserdata);
        }
    }

    public function commissionAjaxSupplierSearch(Request $request){
        if ($request->ajax()) {
            if (!empty($request->input('account'))) {
                $formatuserdata = Account::getSearchAccountData($request->all());
            } else {
                $formatuserdata = Account::getSearchSupplierDatas($request->all());
            }
            
            return response()->json($formatuserdata);
        }
    }

    public function commissionAdd(Request $request){
        /** Checking request is ajax or not */
        if ($request->ajax()) {
            /** Getting form data using methos request */
            $date = $request->input('date');
            $supplier = $request->input('supplier');
            $salesRep = $request->input('sales_rep');
            $commission = $request->input('commission');
            $accountName = $request->input('account_name');

            /** Setting variables */
            $error = false;
            $accountNameArray = [];

            /** If getting zero errors than save the data into commission table */
            foreach ($accountName as $key => $value) {
                DB::table('commission')->insert([
                    'sales_rep' => $salesRep[$key],
                    'supplier' =>  $supplier[$key],
                    'account_name' => $value,
                    'commission' => $commission[$key],
                    'start_date' => date_format(date_create(trim(explode(" - ", $date[$key])[0])),"Y-m-d H:i:s"),
                    'end_date' => date_format(date_create(trim(explode(" - ", $date[$key])[1])),"Y-m-d H:i:s"),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }

            /** Returning the success message after save commission data */
            if ($error == false) {
                return response()->json(['success' => 'Commissions added successfully'], 200);
            }
        }
    }

    public function commissionAjaxFilter(Request $request){
        if ($request->ajax()) {
            $formatuserdata = Commission::getFilterdCommissionData($request->all());
            return response()->json($formatuserdata);
        }
    }

    public function commissionAddView(){
        $sales = DB::table('sales_team')->select('last_name',
        'first_name', 'id')->get();
        return view('admin.commission.commission', ['pageTitle' => 'Add Commission', 'salesRepersantative' => $sales]); 
    }

    public function exportCatalogCsv(Request $request){
        /** Retrieve data based on the provided parameters */
        $filter['search']['value'] = $request->query('search');
        $csv = true;

        /** Fetch data using the parameters and transform it into CSV format */
        /** Replace this with your actual data fetching logic */
        $data = Commission::getFilterdCommissionData($filter, $csv);

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
        $response->headers->set('Content-Disposition', 'attachment; filename="CommissionData_'.now()->format('YmdHis').'.csv"');
  
        /** return $csvResponse; */
        return $response;
    }

    public function editCommission(Request $request){
        if ($request->ajax()) {
            try {
                $updateCommission = Commission::where('id', $request->commission_id)
                ->update(['commission' => $request->commission,
                    'start_date' => date_format(date_create(trim(explode(" - ", $request->input('date'))[0])),"Y-m-d H:i:s"),
                    'end_date' => date_format(date_create(trim(explode(" - ", $request->input('date'))[1])),"Y-m-d H:i:s"),
                    'status' => $request->status
                ]);
                
                if($updateCommission){
                    return response()->json(['success' => 'Commission Updated Successfully'], 200);
                }
            } catch (\Throwable $e) {
                return response()->json(['error' => $e->getMessage()], 200);
            }
        }
    }
}
