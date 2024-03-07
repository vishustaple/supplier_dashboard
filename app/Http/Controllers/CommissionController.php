<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use League\Csv\Writer;
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
            $formatuserdata = Account::getSearchCustomerData($request->input('q'));
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
            // $formatuserdata = Supplier::getSearchSupplierData($request->input('q'));
            return response()->json($formatuserdata);
        }
    }

    public function commissionAdd(Request $request){
        if ($request->ajax()) {
            $customers = $request->get('supplier');
            $suppliers = $request->input('supplier');
            $accountNames = $request->input('account_name');
            $commissions = $request->input('commission');
            $dates = $request->input('date');
    
            // Combine the arrays into a single array for easier processing
            $data = [];
            foreach ($customers as $index => $customer) {
                $data[] = [
                    'customer' => $customer,
                    'supplier' => $suppliers[$index],
                    'account_name' => $accountNames[$index],
                    'commission' => $commissions[$index],
                    'start_date' => date_format(date_create(trim(explode(" - ", $dates[$index])[0])),"Y-m-d H:i:s"),
                    'end_date' => date_format(date_create(trim(explode(" - ", $dates[$index])[1])),"Y-m-d H:i:s"),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
    
            DB::table('commission')->insert($data);
            return response()->json(['success' => 'Commissions added successfully'], 200);
        }
    }

    public function commissionAjaxFilter(Request $request){
        if ($request->ajax()) {
            $formatuserdata = Commission::getFilterdCommissionData($request->all());
            return response()->json($formatuserdata);
        }
    }

    public function commissionAddView(){
        return view('admin.commission.commission', ['pageTitle' => 'Add Commission']); 
    }

    public function exportCatalogCsv(Request $request){
        /** Retrieve data based on the provided parameters */
        $filter['search']['value'] = $request->query('search');
        $csv = true;

        /** Fetch data using the parameters and transform it into CSV format */
        /** Replace this with your actual data fetching logic */
        $data = Commission::getFilterdCommissionData($filter, $csv);
        // echo"<pre>";
        // print_r($data);
        // die;

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
        $response->headers->set('Content-Disposition', 'attachment; filename="CatalogData_'.now()->format('YmdHis').'.csv"');
  
        /** return $csvResponse; */
        return $response;
    }
}
