<?php

namespace App\Http\Controllers;

use Validator;
use League\Csv\Writer;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpFoundation\StreamedResponse;
// use App\Rules\AtLeastOneChecked;

class AccountController extends Controller
{
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
           
            Account::create([
                'customer_number' => $request->customer_id,
                'customer_name' => $request->alies,
                'parent_id' => $request->input('grandparentselect') ?? null,
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
                'created_by'=>'1',
            ]);
            return response()->json(['success' => 'Add account Successfully!'], 200);

        } catch (QueryException $e) {   
            return response()->json(['error' => $e->getMessage()], 200);
        
        }
    }
   
    public function getParent(Request $request){
        // dd($request->all());
        // dd("here");
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

        /** Create a stream for output */
        $stream = fopen('php://temp', 'w+');

        /** Create a new CSV writer instance */
        $csvWriter = Writer::createFromStream($stream);
        
        /** Add column headings */
        $csvWriter->insertOne(['Customer Number', 'Customer Name', 'Supplier Name', 'Account Name', 'Record Type', 'Date']);

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
        $grandparent = Account::select('id','alies')->get();
        return view('admin.account.add',['fromTitle' => $frompageTitle,'currentTitle' => $currentpageTitle,'grandparent'=>$grandparent]);
    }
    public function editAccount(Request $request){
        
        $accountId = $request->id;
        $editAccountData = Account::where('id',$accountId)->first();
        $grandparent = Account::select('id','alies')->get();
        $frompageTitle = $request->routename;
        $currentpageTitle = 'Edit Data';
        return view('admin.account.edit',['fromTitle' => $frompageTitle,'currentTitle' => $currentpageTitle,'account' => $editAccountData,'grandparent'=>$grandparent] );
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
              
                $account->update([
                 'customer_number' => $request->customer_id,
                'customer_name' => $request->alies,
                'parent_id' => $request->input('grandparentSelect'),
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
                'created_by'=>'1',
                ]);

            }
            return response()->json(['success' => 'Account Update Successfully!'], 200);
           
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 200);
        }
    }

}
