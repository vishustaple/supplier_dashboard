<?php

namespace App\Http\Controllers;
use DB;
use Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx; 
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use App\Models\{CategorySupplier, UploadedFiles, OrderDetails, Account};
use Carbon\Carbon;


class ExcelImportController extends Controller
{
    public function index(){
      
        $categorySuppliers = CategorySupplier::all();
        $uploadData = UploadedFiles::orderBy('created_at', 'desc')->get();
        $formattedData = [];
        $cronString=''; 
        $i=1;
        foreach ($uploadData as $item) {
            if ($item->cron == 1) {
                $cronString = 'Pending';
            } elseif ($item->cron == 3) {
                $cronString = 'Uploaded';
            } else {
                // If you don't want to set a default value, you can leave this block empty or skip it.
            }
            // $cronString = $item->cron == 1 ? 'Pending' : 'Uploaded';
            $formattedData[] = [
                $i, 
                getSupplierName($item->supplier_id),
                $item->file_name,
                $cronString,
                $item->created_at->format('m/d/Y'),
                // $item->updated_at->format('m/d/Y'),
            ];
            $i++;
        }
        $data=json_encode($formattedData);
  
       
        return view('admin.export',compact('categorySuppliers','data'));
    }
    public function import(Request $request)
    {
        // dd($request->all());
        $endDateRange = $request->input('enddate');

        /** Split the date range string into start and end dates */
        list($startDate, $endDate) = explode(' - ', $endDateRange);
        
        /** Convert the date strings to the 'YYYY-MM-DD' format */
        $formattedStartDate = Carbon::createFromFormat('m/d/Y', $startDate)->format('Y-m-d');
        $formattedEndDate = Carbon::createFromFormat('m/d/Y', $endDate)->format('Y-m-d');

        $supplierId = $request->supplierselect;
        
        /** Validate the uploaded file */
        $validator = Validator::make(
            [
                'supplierselect'=>$request->supplierselect,
                // 'startdate' => $formattedStartDate,
                'enddate' => $request->input('enddate'),
                'file'      =>  $request->file('file'),
            ],
            [
                'supplierselect'=>'required',
                // 'startdate'=>'required',
                'enddate'=>'required',
                'file' => 'required|file|mimes:xlsx,xls',

            ],
            [
                'enddate.required' => 'The Date field is required. ',
                'supplierselect.required' => 'Please select a supplier. It is a required field.',
               
            ]
        );

        if( $validator->fails() ){  
            $categorySuppliers = CategorySupplier::all();
            // return redirect()->back()->withErrors($validator)->withInput(compact('categorySuppliers'));
            return response()->json(['error' => $validator->errors(), 'categorySuppliers' => $categorySuppliers], 200);
        }
        
        try{
            $reader = new Xlsx(); 
            $spreadSheet = $reader->load($request->file('file'), 2);
            $workSheet = $spreadSheet->getActiveSheet();
            $sheetName = $workSheet->getTitle();
            $sheetCount = $spreadSheet->getSheetCount(); 
        
            /** Variables to store information about the row with the highest number of columns */
            $workSheetArray = $workSheet->toArray();
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        $startIndexValueArray = $valueArrayKey = $maxNonEmptyCount = 0;

        foreach ($workSheetArray as $key=>$value) {
            /** Checking not empty columns */
            $nonEmptyCount = count(array_filter(array_values($value), function ($item) {
                return !empty($item);
            }));
            
            /** If column count is greater then previous row columns count. Then assigen value to '$maxNonEmptyvalue' */
            if ($nonEmptyCount > $maxNonEmptyCount) {
                $maxNonEmptyvalue = $value;
                $startIndexValueArray = $key;
                $maxNonEmptyCount = $nonEmptyCount;
            } 
            
            /** Stop loop after reading 31 rows from excel file */
            if($key > 30){
                break;
            }
        }
        
        /** Remove empty key from the array of excel sheet column name */
        $finalExcelKeyArray = array_values(array_filter($maxNonEmptyvalue, function ($item) {
            return !empty($item);
        }, ARRAY_FILTER_USE_BOTH));
                    
        // print_r($finalExcelKeyArray);
        // die();
        /** Clean up the values */
        $cleanedArray = array_map(function ($value) {
            /** Remove line breaks and trim whitespace */
            return str_replace(["\r", "\n"], '', $value);
        }, $finalExcelKeyArray);

        /** Output the cleaned array */
        // echo"<pre>";
        // print_r($cleanedArray);
        // die;

        $suppliers=[
            '1' => ['SOLD TOACCOUNT','SOLD TO NAME','SHIP TOACCOUNT','SHIP TO NAME','SHIP TO ADDRESS','CATEGORIES','SUB GROUP 1','PRODUCT','DESCRIPTION','GREEN (Y/N)','QUANTITYSHIPPED','ON-CORESPEND','OFF-CORESPEND'],
            
            '2' => ['Track Code', 'Track Code Name', 'Sub track Code', 'Sub Track Code Name','Account Number', 'Account Name', 'Material', 'Material Description','Material Segment', 'Brand Name', 'Bill Date', 'Billing Document','Purchase Order Number', 'Sales Document', 'Name of Orderer', ' Sales Office','Sales Office Name', 'Bill Line No. ', 'Active Price Point', 'Billing Qty','Purchase Amount', 'Freight Billed', 'Tax Billed', 'Total Invoice Price','Actual Price Paid', 'Reference Price', 'Ext Reference Price', 'Diff $','Discount %', 'Invoice Number'],
            
            '3' => ['CUSTOMER GRANDPARENT ID','CUSTOMER GRANDPARENT NM','CUSTOMER PARENT ID','CUSTOMER PARENT NM','CUSTOMER ID','CUSTOMER NM','DEPT','CLASS','SUBCLASS','SKU','Manufacture Item#','Manufacture Name','Product Description','Core Flag','Maxi Catalog/WholesaleFlag','UOM','PRIVATE BRAND','GREEN SHADE','QTY Shipped','Unit Net Price','(Unit) Web Price','Total Spend','Shipto Location','Contact Name','Shipped Date','Invoice #','Payment Method'],
            
            '4' => ['MASTER_CUSTOMER', 'MASTER_NAME', 'BILLTONUMBER', 'BILLTONAME', 'SHIPTONUMBER', 'SHIPTONAME', 'SHIPTOADDRESSLINE1', 'SHIPTOADDRESSLINE2', 'SHIPTOADDRESSLINE3', 'SHIPTOCITY', 'SHIPTOSTATE', 'SHIPTOZIPCODE', 'LASTSHIPDATE', 'SHIPTOCREATEDATE', 'SHIPTOSTATUS', 'LINEITEMBUDGETCENTER', 'CUSTPOREL', 'CUSTPO', 'ORDERCONTACT', 'ORDERCONTACTPHONE', 'SHIPTOCONTACT', 'ORDERNUMBER', 'ORDERDATE', 'SHIPPEDDATE', 'TRANSSHIPTOLINE3', 'SHIPMENTNUMBER', 'TRANSTYPECODE', 'ORDERMETHODDESC', 'PYMTTYPE', 'PYMTMETHODDESC', 'INVOICENUMBER', 'SUMMARYINVOICENUMBER', 'INVOICEDATE', 'CVNCECARDFLAG', 'SKUNUMBER', 'ITEMDESCRIPTION', 'STAPLESADVANTAGEITEMDESCRIPTION', 'SELLUOM', 'QTYINSELLUOM', 'STAPLESOWNBRAND', 'DIVERSITYCD', 'DIVERSITY', 'DIVERSITYSUBTYPECD', 'DIVERSITYSUBTYPE', 'CONTRACTFLAG', 'SKUTYPE', 'TRANSSOURCESYSCD', 'TRANSACTIONSOURCESYSTEM', 'ITEMFREQUENCY', 'NUMBERORDERSSHIPPED', 'QTY', 'ADJGROSSSALES', 'AVGSELLPRICE'],
            
            '5' => ['Customer Num','Customer Name','Item Num','Item Name','Category','Category Umbrella','Price Method','Uo M','Current List','Qty','Ext Price',],
            
            '6' => ['Payer', 'Name Payer', 'Sold-to pt', 'Name Sold-to party', 'Ship-to', 'Name Ship-to', 'Name 3 + Name 4 - Ship-to', 'Street - Ship-to', 'District - Ship-to', 'PostalCode - Ship-to', 'City - Ship-to', 'Country - Ship-to', 'Leader customer 1', 'Leader customer 2', 'Leader customer 3', 'Leader customer 4', 'Leader customer 5', 'Leader customer 6', 'Product hierarchy', 'Section', 'Family', 'Category', 'Sub Category', 'Material', 'Material Description', 'Ownbrand', 'Green product', 'NBS', 'Customer Material', 'Customer description', 'Sales unit', 'Qty. in SKU', 'Sales deal', 'Purchase order type', 'Qty in Sales Unit - P', 'Quantity in SKU - P', 'Number of orders - P', 'Sales Amount - P', 'Tax amount - P', 'Net sales - P', 'Avg Selling Price - P', 'Document Date', 'Sales Document', 'PO number', 'BPO number', 'Invoice list', 'Billing Document', 'Billing Date', 'CAC number', 'CAC description', 'Billing month - P'],

            '7'=>['GP ID','GP Name','202301','202302','202303','202304','202305','202306','202307','202308','202309','202310','202311','202312','202313','202314','202315','202316','202317','202318','202319','202320','202321','202322','202323','202324','202325','202326','2023027','202328','202329','202330','202331','202332','202333','202334','202335','202336','202337','202338','202339','202340','202341','202342','202343','202344','202345','202346','202347','202348','202349','202350','202351','202352'],
        ];

        /** Get the uploaded file */
        $file = $request->file('file');

        /** Generate a timestamp to append to the file name */
        $timesTamp = now()->format('YmdHis');

        /** Append timestamp to the file name */
        $fileName = $timesTamp . '_' . $file->getClientOriginalName();

        /** Define the folder where you want to save the file */
        $destinationPath = public_path('/excel_sheets');

        /** check supllier upload right file or not */
        if (isset($suppliers[$request->supplierselect])) {
          
            $supplierValues = $suppliers[$request->supplierselect];
            // dd(array_diff($supplierValues,$cleanedArray));
            // dd($supplierValues);
            
            if(array_values($supplierValues) === array_values($cleanedArray)){

                /** Get the authenticated user */
                $user = Auth::user();
                $endDateRange = $request->input('enddate');

                // Split the date range string into start and end dates
                list($startDate, $endDate) = explode(' - ', $endDateRange);
                // Convert the date strings to the 'YYYY-MM-DD' format
                $formattedStartDate = Carbon::createFromFormat('m/d/Y', $startDate)->format('Y-m-d');
                $formattedEndDate = Carbon::createFromFormat('m/d/Y', $endDate)->format('Y-m-d');
        
                try{
                    UploadedFiles::create([
                        'supplier_id' => $request->supplierselect,
                        'cron' => UploadedFiles::UPLOAD,
                        'start_date' => $formattedStartDate,
                        'end_date' => $formattedEndDate,
                        'file_name' => $fileName,
                        'created_by' => $user->id,
                    ]); 

                    /** Move the file with the new name */
                    $file->move($destinationPath, $fileName);

                } catch (QueryException $e) {   
                    return response()->json(['error' => $e->getMessage()], 200);
                    // return redirect()->back()->with('error', $e->getMessage());
                }
                return response()->json(['success' => 'Excel file imported successfully!'], 200);
                // return redirect()->back()->with('success', 'Excel file imported successfully!');
            } else {
                return response()->json(['error' => 'Please upload a file that corresponds to the selected supplier.'], 200);
                // return redirect()->back()->with('error', 'Please upload a file that corresponds to the selected supplier.');
            }
        } else {
            echo "Supplier ID ".$request->supplierselect." not found in the array.";
        }
    }
    public function allSupplier(){

        // dd("here");
        $categorySuppliers = CategorySupplier::all();
        $formattedData = [];
        foreach ($categorySuppliers as $suppliers) {
            # code...
            $formattedData[] = [
                $suppliers->id, 
                $suppliers->supplier_name,
                $suppliers->created_at->format('m/d/Y'),
            ];
        }
     
       
        $data=json_encode($formattedData);
        return view('admin.supplier',compact('data'));
    }
     public function allAccount(){
       

        $accounts = Account::with('parent.parent') // Eager load relationships
        ->select('accounts.id', 'accounts.customer_name','accounts.customer_number','accounts.internal_reporting_name','accounts.qbr','accounts.spend_name','accounts.supplier_acct_rep','accounts.management_fee','accounts.record_type','accounts.category_supplier','accounts.cpg_sales_representative','accounts.cpg_customer_service_rep','accounts.sf_cat','accounts.rebate_freq','accounts.member_rebate','accounts.comm_rate',
        DB::raw("parent.customer_name as Parent_Name"),
        DB::raw("grandparent.customer_name as Grand_Parent_Name"))
        ->leftJoin('accounts as parent', 'parent.id', '=', 'accounts.parent_id')
        ->leftJoin('accounts as grandparent', 'grandparent.id', '=', 'parent.parent_id')
        ->orderBy('grandparent.id')
        ->orderBy('parent.id')
        ->orderBy('accounts.id')
        ->get();
        // ->toArray();

// dd($accounts);
        // ->toSql();
         // Print the SQL query
//         echo $accounts->toSql();
// die();
        $formattedAccountData = [];
        $i=1;
        foreach ($accounts as $account) {
            # code...
            $formattedAccountData[] = [
                $i, 
                $account->customer_name,
                $account->customer_number,
                $account->Parent_Name??'-',
                $account->Grand_Parent_Name??'-',
                $account->internal_reporting_name??'-',
                $account->qbr??'-',
                $account->spend_name??'-',
                $account->supplier_acct_rep??'-',
                $account->management_fee??'-',
                $account->record_type??'-',
                $account->category_supplier??'-',
                $account->cpg_sales_representative??'-',
                $account->cpg_customer_service_rep??'-',
                $account->sf_cat??'-',
                $account->rebate_freq??'-',
                $account->member_rebate??'-',
                $account->comm_rate??'-',
                
            ];
            $i++;
        }
     
     
        $accountsdata=json_encode($formattedAccountData);
        $allArray = DB::table('accounts as c1')
        ->select('c3.id as gparent_id', 'c3.customer_name', 'c2.id as parent_id', 'c2.customer_name as Parent Name')
        ->join('accounts as c2', function ($join) {
        $join->on('c2.id', '=', 'c1.parent_id')
            ->whereNotNull('c2.id');
        })
        ->join('accounts as c3', function ($join) {
        $join->on('c3.id', '=', 'c2.parent_id')
            ->whereNotNull('c3.id');
        })
        ->groupBy('c3.id', 'c2.id')
        ->orderBy('c3.id')
        ->orderBy('c2.id')
        ->get()->toArray();
        $resultArray = [];
        foreach ($allArray as $item) {
        $gparentId = $item->gparent_id;
        $parentId = $item->parent_id;
    
        // Check if the gparent_id is already in the result array
        if (!isset($resultArray[$gparentId])) {
            // If not, add it to the result array
            $resultArray[$gparentId] = [
                'id' => $gparentId,
                'name' => $item->customer_name,
            ];
        }
    
        // Check if the parent_id is already in the result array
        if (!in_array($parentId, array_column($resultArray, 'id'))) {
            // If not, add it to the result array
            $resultArray[] = [
                'id' => $parentId,
                'name' => $item->{'Parent Name'}, // Use 'Parent Name' or adjust the property name accordingly
            ];
        }
    }

// Convert the associative array to a simple numeric array
    //    $resultArray = array_values($resultArray);
        $grandparent = Account::select('id','customer_name')->get();
        
        return view('admin.account',compact('accountsdata','grandparent','resultArray'));
     }
}