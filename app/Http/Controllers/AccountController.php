<?php

namespace App\Http\Controllers;

use League\Csv\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use App\Models\{Account, CategorySupplier};
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
            $pageTitle = 'Account Details';
            $account = Account::select('master_account_detail.account_name as account_name')->where('master_account_detail.id', $id)->first();
            return view('admin.account_detail', compact('account', 'pageTitle'));
        }
        
        $missingAccount = Account::whereNull('account_name')->orWhere('account_name', '')->get();
        $totalmissingaccount=count($missingAccount);

        $grandparent = Account::getSearchGPNameData();
        $parent = Account::getSearchPNameData();

        $grandparent_id = Account::getSearchGPNumberData();
        $parent_id = Account::getSearchPNumberData();

        return view('admin.account' ,compact(['totalmissingaccount' => 'totalmissingaccount', 'grandparent' => 'grandparent', 'parent' => 'parent', 'grandparent_id' => 'grandparent_id', 'parent_id' => 'parent_id']));
    }

    public function getAccountsDetailWithAjax(Request $request){
        if ($request->ajax()) {
            $formatuserdata = Account::getFilterdAccountsAllData($request->all());
            return response()->json($formatuserdata);
        }
    }

    public function getEmptyAccountNameAccounts(Request $request){
        if ($request->ajax()) {
            $missingAccount = Account::whereNull('account_name')->orWhere('account_name', '')->get();
            $totalmissingaccount = count($missingAccount);
            return response()->json(['success' => $totalmissingaccount], 200);
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

    public function editAccountName(Request $request){
        $accoundid = $request->account_id;
        $accountname =$request->account_name;
        $parentName = $request->parent_name;
        $parentNumber =$request->parent_number;
        $categoryName = $request->catagory_name;
        $customerName =$request->customer_name;
        $validator = Validator::make($request->all(),
            [
                // 'parent_name'=>'required|string|max:255',
                'account_name'=>'required|string|max:255',
                // 'parent_number'=>'required|string|max:255',
                // 'customer_name'=>'required|string|max:255',
                // 'category_name'=>'required|string|max:255',
            ]
        );

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 200);
        } else {
            try {
                $updateAccountName = Account::where('id', $accoundid)->update([
                    'account_name' => $accountname,
                    'parent_name' => $parentName,
                    'parent_id' => $parentNumber,
                    'record_type' => $categoryName,
                    'customer_name' => $customerName,
                ]);

                if ($updateAccountName) {
                    return response()->json(['success' => 'Account Data Update Successfully!'], 200);
                }
            } catch (\Throwable $e) {
                return response()->json(['error' => $e->getMessage()], 200);
            }
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

    public function getAccountNumber(Request $request){
        if ($request->ajax()) {
            $accountNumber = Account::select('account_number')->where('account_name', 'LIKE', '%' . $request->input("account_name") . '%')->get()->toArray();
            return response()->json($accountNumber);
        }  
    }
}
