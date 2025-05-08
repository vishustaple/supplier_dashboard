<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Account, CategorySupplier, Rebate};
use Illuminate\Support\Facades\Auth;
use League\Csv\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RebateController extends Controller
{
    public function __construct(){
        $this->middleware('permission:Rebate')->only(['index', 'getUpdateRebateWithAjax', 'getRebateWithAjax', 'rebateCount', 'rebateUpdate']);
        $this->middleware('permission:Rebate Edit')->only(['rebateUpdate']);
    }

    public function index(Request $request, $rebateType, $id=null){
        if (!isset($id)) {
            $id = $request->query('id');
        }

        $setPageTitleArray = [
            'rebate' => 'Rebate',
            'edit_rebate' => 'Edit Rebate',
        ];

        if ($rebateType == 'edit_rebate') {
            return view('admin.rebate.'. $rebateType .'', ['pageTitle' => $setPageTitleArray[$rebateType]]);
        } else {
            $missingRebate = Account::select('r1.id')
            ->leftJoin('rebate AS r1', function($join) {
                $join->on('master_account_detail.account_name', '=', 'r1.account_name')
                ->on('master_account_detail.supplier_id', '=', 'r1.supplier');
            })
            ->whereNotNull('master_account_detail.account_name')
            ->where('master_account_detail.account_name', '!=', '')
            ->whereNull('r1.volume_rebate')
            ->whereNull('r1.incentive_rebate')
            ->groupBy('master_account_detail.account_name')
            ->groupBy('master_account_detail.supplier_id')
            ->getQuery()->getCountForPagination();
            return view('admin.rebate.'. $rebateType .'', ['pageTitle' => $setPageTitleArray[$rebateType], 'categorySuppliers' => CategorySupplier::where('show', 0)->whereNotIn('id', [15])->get(), 'totalMissingRebate' => $missingRebate]);
        }
    }

    public function getUpdateRebateWithAjax(Request $request){
        if ($request->ajax()) {
            $formatuserdata = Account::getFilterdUpdateRebateData($request->all());
            return response()->json($formatuserdata);
        }
    }

    public function getRebateWithAjax(Request $request){
        if ($request->ajax()) {
            $formatuserdata = Account::getFilterdRebateData($request->all());
            return response()->json($formatuserdata);
        }
    }

    public function getRebateDownload(Request $request){
        /** Retrieve data based on the provided parameters */
        $filter['supplier_id'] = $request->input('supplier_id');

        /** Fetch data using the parameters and transform it into CSV format */
        /** Replace this with your actual data fetching logic */
        $data = Account::getFilterdRebateData($filter, true);

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
        $response->headers->set('Content-Disposition', 'attachment; filename="SupplierRebates_'.now()->format('YmdHis').'.csv"');

        /** return $csvResponse; */
        return $response;
    }

    public function rebateCount(Request $request){
        if ($request->ajax()) {
            $missingRebate = Account::select('r1.id')
            // ->leftJoin('rebate  AS r1', 'master_account_detail.account_name', '=', 'r1.account_name')
            ->leftJoin('rebate AS r1', function($join) {
                $join->on('master_account_detail.account_name', '=', 'r1.account_name')
                ->on('master_account_detail.supplier_id', '=', 'r1.supplier');
            })
            ->whereNotNull('master_account_detail.account_name')
            ->where('master_account_detail.account_name', '!=', '')
            ->whereNull('r1.volume_rebate')
            ->whereNull('r1.incentive_rebate')
            ->groupBy('master_account_detail.account_name')
            ->groupBy('master_account_detail.supplier_id')
            ->getQuery()->getCountForPagination();
            return response()->json(['success' => $missingRebate], 200);
        }
    }

    public function rebateUpdate(Request $request) {
        if ($request->ajax()) {
            // dd($request->all());
            $rebate = Rebate::where(['account_name'=> $request->account_name, 'supplier'=> $request->supplier_id])->first();

            /** Check if the record exists */
            if($rebate) {
                /** Update the existing record with validated data */
                $rebate->update(['account_name' => $request->input('account_name'),
                'supplier' => $request->input('supplier_id'),
                'volume_rebate' => $request->input('volume_rebate'),
                'incentive_rebate' => $request->input('incentive_rebate'),
                ]);
            
                return response()->json(['success' => 'Rebate updated successfully'], 200);
            } else {
                /** Create a new record with validated data */
                $rebate = Rebate::create(['account_name' => $request->input('account_name'),
                    'supplier' => $request->input('supplier_id'),
                    'volume_rebate' => $request->input('volume_rebate'),
                    'incentive_rebate' => $request->input('incentive_rebate'),
                ]);

                return response()->json(['success' => 'Record added successfully'], 200);
            }
        } else {
            return response()->json(['success' => 'User do not have permission to update and add rebate.'], 200);
        }
    }
}
