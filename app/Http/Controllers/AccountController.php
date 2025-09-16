<?php
namespace App\Http\Controllers;

use League\Csv\Writer;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Log, Mail, Validator};
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountController extends Controller
{
    public function editCustomerName() {
        $pageTitle = 'Accounts Data';

        $missingAccount = Account::select(
            'master_account_detail.id AS id',
            'customers.customer_name AS customer_name',
            'master_account_detail.supplier_id AS supplier_id',
            'master_account_detail.account_number AS account_number',
        )
        ->join('customers', 'customers.id', '=', 'master_account_detail.customer_id')
        ->whereNull('master_account_detail.account_name')
        ->orWhere('master_account_detail.account_name', '')
        ->get();

        return view('admin.account.edit_customer_name',compact('missingAccount', 'pageTitle'));
    }

    public function allAccount(Request $request, $id=null) {
        if (!isset($id)) {
            $id = $request->query('id');
        }

        if (isset($id)) {
            $pageTitle = 'Account Details';
            
            $account = Account::select('master_account_detail.account_name as account_name')
            ->where('master_account_detail.id', $id)
            ->first();

            return view('admin.account_detail', compact('account', 'pageTitle'));
        }
        
        $missingAccount = Account::whereNull('account_name')
        ->orWhere('account_name', '')
        ->get();

        $totalmissingaccount = count($missingAccount);

        return view('admin.account' ,compact(['totalmissingaccount' => 'totalmissingaccount']));
    }

    public function getAccountsDetailWithAjax(Request $request) {
        if ($request->ajax()) {
            $formatuserdata = Account::getFilterdAccountsAllData($request->all());
            return response()->json($formatuserdata);
        }
    }

    public static function getEmptyAccountNameAccounts() {
        $missingAccount = Account::whereNull('account_name')
        ->orWhere('account_name', '')
        ->get();
        $totalmissingaccount = count($missingAccount);
        return response()->json(['success' => $totalmissingaccount], 200);
    }
   
    public function getAccountsWithAjax(Request $request) {
        if ($request->ajax()) {
            $formatuserdata = Account::getFilterdAccountsData($request->all());
            return response()->json($formatuserdata);
        }
    }

    public function exportAccountCsv(Request $request) {
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
        $csvWriter->insertOne([
            'Customer Number',
            'Customer Name',
            'Account Name',
            'Grand Parent Name',
            'Parent Name',
            'Volume Rebate',
            'Sales Representative',
            'Customer Service Representative',
            'Member Rebate',
            'Temp Active Date',
            'Temp End Date',
            'Internal Reporting Name',
            'Qbr',
            'Spend Name',
            'Supplier Acct Rep',
            'Management Fee',
            'Category',
            'Supplier',
            'Cpg Sales Representative',
            'Cpg Customer Service Rep',
            'Sf Cat',
            'Rebate Freq',
            'Comm Rate'
        ]);

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

    public function  Back() {
        $url = route('account');
        return redirect($url);
    }

    public function removeAccount(Request $request) {
        $accountId = $request->id;
        $account = Account::find($accountId);
        if($account) {
            $account->delete();
            return response()->json(['success' => 'Account deleted successfully']);
        } else {
            return response()->json(['error' => 'Account not found'], 404);
        }
    }

    public function editAccountName(Request $request) {
        $accoundid = $request->account_id;
        $parentName = $request->parent_name;
        $customerId = $request->customer_id;
        $accountname = $request->account_name;
        $parentNumber = $request->parent_number;
        $categoryName = $request->catagory_name;
        $customerName = $request->customer_name;

        $validator = Validator::make(
            $request->all(),
            [
                'account_name' => 'required|string|max:255',
            ]
        );

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 200);
        } else {
            try {
                DB::table('customers')
                ->where('id', $customerId)
                ->update(['customer_name' => $customerName]);
                
                $updateAccountName = Account::where('id', $accoundid)->update([
                    'parent_name' => $parentName,
                    'parent_id' => $parentNumber,
                    'account_name' => $accountname,
                    'record_type' => $categoryName,
                ]);

                if ($updateAccountName) {
                    return response()->json(['success' => 'Account Data Update Successfully!'], 200);
                }
            } catch (\Throwable $e) {
                return response()->json(['error' => $e->getMessage()], 200);
            }
        }
    }

    public function updateMissingAccount(Request $request) {
       $missingid = $request->id;
       $missingvalue = $request->ColumnValue;
        try {
            $updateMissingAccount = Account::where('id', $missingid)
            ->update(['account_name' => $missingvalue]);

            if ($updateMissingAccount) {
                /** We use try catch to handle errors during email send */
                try {
                    Log::info('Attempting to send email...');
                    echo "Attempting to send email...";

                    /** Setting the email where we want to send email */
                    $emails = [
                        'vishustaple.in@gmail.com',
                        'anurag@centerpointgroup.com',
                        'santosh@centerpointgroup.com',
                        'mgaballa@centerpointgroup.com',
                    ];
        
                    $data = [
                        'link' => url('admin/rebate/edit_rebate'),
                        'body' => 'The following account need to have their rebates updated: -',
                        'account_name' => $missingvalue,
                    ];
        
                    /** Sending email here */
                    Mail::send('mail.newaccount', $data, function($message) use ($emails) {
                        $message->to($emails)
                                ->subject('New Rebate in Database');
                    });
        
                    echo "Email sent successfully";
                    Log::info('Email sent successfully');
                } catch (\Exception $e) {
                    /** Handle the exception here */
                    Log::error('Email sending failed: ' . $e->getMessage());
                    echo "Email sending failed: " . $e->getMessage();
                }

                return response()->json(['success' => 'Account Name Update Successfully!'], 200);
            }
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 200);
        }
    }

    public function getAccountNumber(Request $request) {
        if ($request->ajax()) {
            $accountNumber = Account::select('account_number')
            ->where(
                'account_name',
                'LIKE',
                '%' . $request->input("account_name") . '%'
            )
            ->get()
            ->toArray();

            return response()->json($accountNumber);
        }  
    }
}
