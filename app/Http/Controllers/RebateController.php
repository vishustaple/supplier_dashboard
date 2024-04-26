<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Account, Rebate};
use Illuminate\Support\Facades\DB;

class RebateController extends Controller
{
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
            ->leftJoin('rebate  AS r1', 'master_account_detail.account_name', '=', 'r1.account_name')
            ->whereNotNull('master_account_detail.account_name')
            ->where('master_account_detail.account_name', '!=', '')
            ->whereNull('r1.volume_rebate')
            ->whereNull('r1.incentive_rebate')
            ->groupBy('master_account_detail.account_name')
            ->getQuery()->getCountForPagination();
            return view('admin.rebate.'. $rebateType .'', ['pageTitle' => $setPageTitleArray[$rebateType], 'totalMissingRebate' => $missingRebate]);
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

    public function rebateCount(Request $request){
        if ($request->ajax()) {
            $missingRebate = Account::select('r1.id')
            ->leftJoin('rebate  AS r1', 'master_account_detail.account_name', '=', 'r1.account_name')
            ->whereNotNull('master_account_detail.account_name')
            ->where('master_account_detail.account_name', '!=', '')
            ->whereNull('r1.volume_rebate')
            ->whereNull('r1.incentive_rebate')
            ->groupBy('master_account_detail.account_name')
            ->getQuery()->getCountForPagination();
            return response()->json(['success' => $missingRebate], 200);
        }
    }

    public function rebateUpdate(Request $request){
        if ($request->ajax()) {
            // dd($request->all());
            $rebate = Rebate::where('account_name', $request->account_name)->first();

            /** Check if the record exists */
            if($rebate) {
                /** Update the existing record with validated data */
                $rebate->update(['account_name' => $request->input('account_name'),
                'volume_rebate' => $request->input('volume_rebate'),
                'incentive_rebate' => $request->input('incentive_rebate'),
                ]);
            
                return response()->json(['success' => 'Rebate updated successfully'], 200);
            } else {
                /** Create a new record with validated data */
                $rebate = Rebate::create(['account_name' => $request->input('account_name'),
                    'volume_rebate' => $request->input('volume_rebate'),
                    'incentive_rebate' => $request->input('incentive_rebate'),
                ]);

                return response()->json(['success' => 'Record added successfully'], 200);
            }
        }
    }
}
