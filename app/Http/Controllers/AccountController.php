<?php

namespace App\Http\Controllers;

use DB;
use Validator;
use League\Csv\Writer;
use App\Models\{Account, CategorySupplier};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpFoundation\StreamedResponse;
// use App\Rules\AtLeastOneChecked;

class AccountController extends Controller
{
    public function editCustomerName(){
        $missingAccount = Account::whereNull('account_name')->orWhere('account_name', '')->get();
        $pageTitle = 'Accounts Data';
        return view('admin.account.edit_customer_name',compact('missingAccount', 'pageTitle'));
    }

    public function allAccount(Request $request, $id=null){
        if (!isset($id)) {
            $id = $request->query('id');
        }

        if(isset($id)){
            $account = Account::select(
                'master_account_detail.id as id',
                'master_account_detail.account_number as customer_number',
                'master_account_detail.customer_name as customer_name',
                'master_account_detail.account_name as account_name',
                'master_account_detail.volume_rebate as volume_rebate',
                'master_account_detail.member_rebate as member_rebate',
                'master_account_detail.temp_active_date as temp_active_date',
                'master_account_detail.temp_end_date as temp_end_date',
                'master_account_detail.record_type as record_type',
                'master_account_detail.cpg_sales_representative as cpg_sales_representative',
                'master_account_detail.cpg_customer_service_rep as cpg_customer_service_rep',
                'suppliers.supplier_name as category_supplier',
                'master_account_detail.parent_name as parent_name',
                'master_account_detail.grandparent_name as grand_parent_name')
                ->leftJoin('suppliers', 'suppliers.id', '=', 'master_account_detail.category_supplier')
            ->where('master_account_detail.id','=', $id)->first();

            return view('admin.viewdetail',compact('account'));
        }
        $missingAccount = Account::whereNull('account_name')->orWhere('account_name', '')->get();
        $totalmissingaccount=count($missingAccount);
        // dd($totalmissingaccount);
        return view('admin.account' ,compact('totalmissingaccount'));
    }

    public function addAccount(Request $request){

    //   dd($request->all());
        $validator = Validator::make(
            [
                'customer_id'=> $request->customer_id,
                'customer_name' => $request->customer_name,
                'account_name' => $request->account_name,
                'volume_rebate' =>$request->volume_rebate,
                'sales_representative' =>$request->sales_representative,
                'customer_service_representative' =>$request->customer_service_representative ,
                'member_rebate' =>$request->member_rebate,
                'temp_active_date' =>$request->temp_active_date ,
                'temp_end_date' =>$request->temp_end_date,
                'internal_reporting_name' =>$request->internal_reporting_name,
                'qbr' => $request->qbr,
                'spend_name' =>$request->spend_name ,
                'supplier_acct_rep'=> $request->supplier_acct_rep,
                'management_fee' =>$request->management_fee ,
                'record_type' =>$request->record_type,
                'category_supplier' =>$request->category_supplier ,
                'cpg_sales_representative' =>$request->cpg_sales_representative,
                'cpg_customer_service_rep' => $request->cpg_customer_service_rep,
                'sf_cat' => $request->sf_cat,
                'rebate_freq' => $request->rebate_freq,
                'comm_rate' =>$request->comm_rate,
                // 'parent'=> $request->parent,
                // 'grandparent'=>$request->grandparent,


            ],
            [
                'customer_id'=>'required|unique:accounts,customer_number',
                'customer_name'=>'required|regex:/^[a-zA-Z0-9\s]+$/',
                // 'parent' => 'nullable',
                // 'grandparentselect' => 'nullable|required_if:parent,1'

            ],
          
             );

        if( $validator->fails() ){  
           
            return response()->json(['error' => $validator->errors()], 200);
        }

        if($request->parent){
            if(empty($request->grandparentSelect)){
                return response()->json(['error' => "The GrandParent Field is Required."], 200);
            }
        }

        try{
            $user = Auth::user();
            Account::create([
                'qbr' => $request->qbr,
                'created_by'=> $user->id,
                'customer_name' => $request->customer_name,
                'sf_cat' => $request->sf_cat,
                'comm_rate' =>$request->comm_rate,
                'spend_name' =>$request->spend_name ,
                'record_type' =>$request->record_type,
                'rebate_freq' => $request->rebate_freq,
                'account_name' => $request->account_name,
                'volume_rebate' =>$request->volume_rebate,
                'member_rebate' =>$request->member_rebate,
                'temp_end_date' =>$request->temp_end_date,
                'customer_number' => $request->customer_id,
                'management_fee' =>$request->management_fee ,
                'temp_active_date' =>$request->temp_active_date ,
                'supplier_acct_rep'=> $request->supplier_acct_rep,
                'category_supplier' =>$request->category_supplier ,
                'sales_representative' =>$request->sales_representative,
                'parent_id' => $request->input('grandparentSelect') ?? null,
                'internal_reporting_name' =>$request->internal_reporting_name,
                'cpg_sales_representative' =>$request->cpg_sales_representative,
                'cpg_customer_service_rep' => $request->cpg_customer_service_rep,
                'customer_service_representative' =>$request->customer_service_representative ,
            ]);

            return response()->json(['success' => 'Add account Successfully!'], 200);
        } catch (QueryException $e) {   
            return response()->json(['error' => $e->getMessage()], 200);
        }
    }
   
    public function getAccountsWithAjax(Request $request){
        if ($request->ajax()) {
            $formatuserdata = Account::getFilterdAccountsData($request->all());
            return response()->json($formatuserdata);
        }
    }

    public function exportAccountCsv(Request $request){
        /** Retrieve data based on the provided parameters */
        $filter = [];
        $csv = true;

        /** Fetch data using the parameters and transform it into CSV format */
        /** Replace this with your actual data fetching logic */
        $data = Account::getFilterdAccountsData($filter, $csv);
        // echo"<pre>";
        // print_r($data);
        // die;
        /** Create a stream for output */
        $stream = fopen('php://temp', 'w+');

        /** Create a new CSV writer instance */
        $csvWriter = Writer::createFromStream($stream);
        
        /** Add column headings */
        $csvWriter->insertOne(['Customer Number', 'Customer Name', 'Account Name', 'Grand Parent Name', 'Parent Name', 'Volume Rebate', 'Sales Representative', 'Customer Service Representative', 'Member Rebate', 'Temp Active Date', 'Temp End Date', 'Internal Reporting Name', 'Qbr', 'Spend Name', 'Supplier Acct Rep', 'Management Fee', 'Category', 'Supplier', 'Cpg Sales Representative', 'Cpg Customer Service Rep', 'Sf Cat', 'Rebate Freq', 'Comm Rate']);

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
        $response->headers->set('Content-Disposition', 'attachment; filename="AccountData_'.now()->format('YmdHis').'.csv"');
  
        /** return $csvResponse; */
        return $response;
    }
    /** creat account form  */

    public function createAccount(){
        $frompageTitle = 'account';
        $currentpageTitle = 'Edit Account Data';
        $categorySuppliers = CategorySupplier::all();
        $grandparent = Account::select('id','customer_name')->get();
        return view('admin.account.add',['categorySuppliers' => $categorySuppliers, 'fromTitle' => $frompageTitle,'currentTitle' => $currentpageTitle,'grandparent'=>$grandparent]);
    }
    public function editAccount(Request $request){
        $accountId = $request->id;
        $editAccountData = Account::where('id',$accountId)->first();
        $grandparent = Account::select('id','customer_name')->get();
        $categorySuppliers = CategorySupplier::all();
        $frompageTitle = $request->routename;
        $currentpageTitle = 'Edit Data';
        return view('admin.account.edit',['categorySuppliers' => $categorySuppliers, 'fromTitle' => $frompageTitle,'currentTitle' => $currentpageTitle,'account' => $editAccountData,'grandparent'=>$grandparent] );
    }
    public function  Back()
    {
        $url = route('account');
        return redirect($url);
    }
    public function removeAccount(Request $request){
        $accountId = $request->id;
        $account = Account::find($accountId);
        if($account) {
            $account->delete();
            return response()->json(['success' => 'Account deleted successfully']);
        } else {
            return response()->json(['error' => 'Account not found'], 404);
        }
    }
    public function updateAccount(Request $request){
       
        $validator = Validator::make(
            [
                'customer_id'=> $request->customer_id,
                'customer_name' => $request->customer_name,

            ],
            [
                'customer_id' => 'required|unique:accounts,customer_number,' . $request->account_id,
                'customer_name'=>'required|regex:/^[a-zA-Z0-9\s]+$/',
            ],
          
             );

        if( $validator->fails() ){  
           
            return response()->json(['error' => $validator->errors()], 200);
        }
        if($request->parent){
            if(empty($request->grandparentSelect)){
            return response()->json(['error' => "The GrandParent Field is Required."], 200);
            }
        }
        try {
            $account = Account::find($request->account_id);
       
            if($account){
                $user = Auth::user();
                $account->update([
                    'created_by'=> $user->id,
                    'qbr' => $request->qbr,
                    'customer_name' => $request->customer_name,
                    'sf_cat' => $request->sf_cat,
                    'comm_rate' =>$request->comm_rate,
                    'spend_name' =>$request->spend_name ,
                    'record_type' =>$request->record_type,
                    'rebate_freq' => $request->rebate_freq,
                    'account_name' => $request->account_name,
                    'member_rebate' =>$request->member_rebate,
                    'volume_rebate' =>$request->volume_rebate,
                    'temp_end_date' =>$request->temp_end_date,
                    'customer_number' => $request->customer_id,
                    'management_fee' =>$request->management_fee ,
                    'temp_active_date' =>$request->temp_active_date ,
                    'category_supplier' =>$request->category_supplier ,
                    'supplier_acct_rep'=> $request->supplier_acct_rep,
                    'parent_id' => $request->input('grandparentSelect'),
                    'sales_representative' =>$request->sales_representative,
                    'internal_reporting_name' =>$request->internal_reporting_name,
                    'cpg_sales_representative' =>$request->cpg_sales_representative,
                    'cpg_customer_service_rep' => $request->cpg_customer_service_rep,
                    'customer_service_representative' =>$request->customer_service_representative ,
                ]);

            }
            return response()->json(['success' => 'Account Update Successfully!'], 200);
           
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 200);
        }
    }
    public function updateMissingAccount(Request $request){
       $missingid = $request->id;
       $missingvalue =$request->ColumnValue;
       try {
        $updateMissingAccount = Account::where('id', $missingid)->update(['account_name' => $missingvalue]);
        if($updateMissingAccount){
            return response()->json(['success' => 'Account Name Update Successfully!'], 200);
        }
       } catch (\Throwable $e) {
        return response()->json(['error' => $e->getMessage()], 200);
       }

    }
}
