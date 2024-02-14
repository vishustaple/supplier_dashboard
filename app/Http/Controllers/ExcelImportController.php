<?php

namespace App\Http\Controllers;

use DB;
use Validator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\QueryException;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx; 
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use App\Models\{CategorySupplier, UploadedFiles, Account, ExcelData, OrderDetails, Order};


class ExcelImportController extends Controller
{
    public function index(){
      
        $categorySuppliers = CategorySupplier::all();

        $uploadData = UploadedFiles::with(['createdByUser:id,first_name,last_name'])->withTrashed()->orderBy('id', 'desc')->get();
        // echo"<pre>";
        // print_r($uploadData);
        // die;
        $formattedData = [];
        $cronString=''; 
        $i=1;

        foreach ($uploadData as $item) {
            if ($item->cron == 1) {
                $cronString = 'Pending';
            } elseif ($item->cron == 2) {
                $cronString = 'Processing';
            } else {
                $cronString = 'Uploaded';
            }

            $formattedData[] = [
                getSupplierName($item->supplier_id),
                '<div class="file_td">'.$item->file_name.'</div>',
                $cronString,
                $item->createdByUser->first_name.' '.$item->createdByUser->last_name,
                $item->created_at->format('m/d/Y'),
                (isset($item->delete) && !empty($item->delete)) ? ('<div class="spinner"><div class="bounce1"></div><div class="bounce2"></div><div class="bounce3"></div></div>') : ((isset($item->deleted_at) && !empty($item->deleted_at) ? '<button class="btn btn-danger btn-xs remove invisible" ><i class="fa-solid fa-trash"></i></button>' : '<button data-id="'.$item->id.'" class="btn btn-danger btn-xs remove" title="Remove File"><i class="fa-solid fa-trash"></i></button>')),
            ];
            $i++;
        }
        $data=json_encode($formattedData);
       
        return view('admin.export',compact('categorySuppliers','data'));
    }
    public function import(Request $request)
    {
        // dd($request->all());
        ini_set('memory_limit', '1024M');

        $endDateRange = $request->input('enddate');

        // /** Split the date range string into start and end dates */
        if(!empty($endDateRange )){
            list($startDate, $endDate) = explode(' - ', $endDateRange);
            
            /** Convert the date strings to the 'YYYY-MM-DD' format */
            $formattedStartDate = Carbon::createFromFormat('m/d/Y', $startDate)->format('Y-m-d');
            $formattedEndDate = Carbon::createFromFormat('m/d/Y', $endDate)->format('Y-m-d');
        }
        
        $supplierId = $request->supplierselect;
        
        /** Validate the uploaded file */
        if ($request->supplierselect == 1) {
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
                    'enddate' => 'required_if:supplierselect,1',
                    'file' => 'required|file|mimes:xlsx,xls',
    
                ],
                [
                    'enddate.required' => 'The date field is required. ',
                    'supplierselect.required' => 'Please select a supplier. It is a required field.',
                   
                ]
            );
        } else {
            $validator = Validator::make(
                [
                    'supplierselect'=>$request->supplierselect,
                    'file'      =>  $request->file('file'),
                ],
                [
                    'supplierselect'=>'required',
                    'file' => 'required|file|mimes:xlsx,xls',
    
                ],
                [
                    'supplierselect.required' => 'Please select a supplier. It is a required field.',
                ]
            );
        }

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

        // $suppliers=[
        //     '1' => ['SOLD TOACCOUNT','SOLD TO NAME','SHIP TOACCOUNT','SHIP TO NAME','SHIP TO ADDRESS','CATEGORIES','SUB GROUP 1','PRODUCT','DESCRIPTION','GREEN (Y/N)','QUANTITYSHIPPED','ON-CORESPEND','OFF-CORESPEND'],
            
        //     '2' => ['Track Code', 'Track Code Name', 'Sub track Code', 'Sub Track Code Name','Account Number', 'Account Name', 'Material', 'Material Description','Material Segment', 'Brand Name', 'Bill Date', 'Billing Document','Purchase Order Number', 'Sales Document', 'Name of Orderer', ' Sales Office','Sales Office Name', 'Bill Line No. ', 'Active Price Point', 'Billing Qty','Purchase Amount', 'Freight Billed', 'Tax Billed', 'Total Invoice Price','Actual Price Paid', 'Reference Price', 'Ext Reference Price', 'Diff $','Discount %', 'Invoice Number'],
            
        //     '3' => ['CUSTOMER GRANDPARENT ID','CUSTOMER GRANDPARENT NM','CUSTOMER PARENT ID','CUSTOMER PARENT NM','CUSTOMER ID','CUSTOMER NM','DEPT','CLASS','SUBCLASS','SKU','Manufacture Item#','Manufacture Name','Product Description','Core Flag','Maxi Catalog/WholesaleFlag','UOM','PRIVATE BRAND','GREEN SHADE','QTY Shipped','Unit Net Price','(Unit) Web Price','Total Spend','Shipto Location','Contact Name','Shipped Date','Invoice #','Payment Method'],
            
        //     '4' => ['MASTER_CUSTOMER', 'MASTER_NAME', 'BILLTONUMBER', 'BILLTONAME', 'SHIPTONUMBER', 'SHIPTONAME', 'SHIPTOADDRESSLINE1', 'SHIPTOADDRESSLINE2', 'SHIPTOADDRESSLINE3', 'SHIPTOCITY', 'SHIPTOSTATE', 'SHIPTOZIPCODE', 'LASTSHIPDATE', 'SHIPTOCREATEDATE', 'SHIPTOSTATUS', 'LINEITEMBUDGETCENTER', 'CUSTPOREL', 'CUSTPO', 'ORDERCONTACT', 'ORDERCONTACTPHONE', 'SHIPTOCONTACT', 'ORDERNUMBER', 'ORDERDATE', 'SHIPPEDDATE', 'TRANSSHIPTOLINE3', 'SHIPMENTNUMBER', 'TRANSTYPECODE', 'ORDERMETHODDESC', 'PYMTTYPE', 'PYMTMETHODDESC', 'INVOICENUMBER', 'SUMMARYINVOICENUMBER', 'INVOICEDATE', 'CVNCECARDFLAG', 'SKUNUMBER', 'ITEMDESCRIPTION', 'STAPLESADVANTAGEITEMDESCRIPTION', 'SELLUOM', 'QTYINSELLUOM', 'STAPLESOWNBRAND', 'DIVERSITYCD', 'DIVERSITY', 'DIVERSITYSUBTYPECD', 'DIVERSITYSUBTYPE', 'CONTRACTFLAG', 'SKUTYPE', 'TRANSSOURCESYSCD', 'TRANSACTIONSOURCESYSTEM', 'ITEMFREQUENCY', 'NUMBERORDERSSHIPPED', 'QTY', 'ADJGROSSSALES', 'AVGSELLPRICE'],
            
        //     '5' => ['Customer Num','Customer Name','Item Num','Item Name','Category','Category Umbrella','Price Method','Uo M','Current List','Qty','Ext Price',],
            
        //     '6' => ['Payer', 'Name Payer', 'Sold-to pt', 'Name Sold-to party', 'Ship-to', 'Name Ship-to', 'Name 3 + Name 4 - Ship-to', 'Street - Ship-to', 'District - Ship-to', 'PostalCode - Ship-to', 'City - Ship-to', 'Country - Ship-to', 'Leader customer 1', 'Leader customer 2', 'Leader customer 3', 'Leader customer 4', 'Leader customer 5', 'Leader customer 6', 'Product hierarchy', 'Section', 'Family', 'Category', 'Sub Category', 'Material', 'Material Description', 'Ownbrand', 'Green product', 'NBS', 'Customer Material', 'Customer description', 'Sales unit', 'Qty. in SKU', 'Sales deal', 'Purchase order type', 'Qty in Sales Unit - P', 'Quantity in SKU - P', 'Number of orders - P', 'Sales Amount - P', 'Tax amount - P', 'Net sales - P', 'Avg Selling Price - P', 'Document Date', 'Sales Document', 'PO number', 'BPO number', 'Invoice list', 'Billing Document', 'Billing Date', 'CAC number', 'CAC description', 'Billing month - P'],

        //     '7'=>['GP ID','GP Name','202301','202302','202303','202304','202305','202306','202307','202308','202309','202310','202311','202312','202313','202314','202315','202316','202317','202318','202319','202320','202321','202322','202323','202324','202325','202326','2023027','202328','202329','202330','202331','202332','202333','202334','202335','202336','202337','202338','202339','202340','202341','202342','202343','202344','202345','202346','202347','202348','202349','202350','202351','202352'],
        // ];


        $suppliers=[
            '1' => ['SOLD TO NAME', 'SOLD TOACCOUNT', 'ON-CORESPEND'],
            '2' => ['Track Code', 'Track Code Name', 'Sub track Code', 'Sub Track Code Name', 'Account Name', 'Account Number', 'Actual Price Paid', 'Invoice Number', 'Bill Date'],
            '3' => ['CUSTOMER NM', 'CUSTOMER GRANDPARENT ID', 'CUSTOMER GRANDPARENT NM', 'CUSTOMER PARENT ID', 'CUSTOMER PARENT NM', 'CUSTOMER ID', 'Total Spend', 'Invoice #', 'Shipped Date'],
            '4' => ['MASTER_CUSTOMER', 'MASTER_CUSTOMER', 'ADJGROSSSALES', 'INVOICENUMBER', 'INVOICEDATE'],
            '5' => ['Customer Name', 'Customer Num', 'Current List', 'Invoice Num', 'Invoice Date'],
            '6' => ['Leader customer 2', 'Leader customer 3', 'Leader customer 4', 'Leader customer 5', 'Leader customer 6', 'Leader customer 1', 'Sales Amount - P', 'Billing Document', 'Billing Date'],
            '7' => ['Account ID'],
            '8' => ['CUSTOMER NM', 'CUSTOMER GRANDPARENT ID', 'CUSTOMER GRANDPARENT NM', 'CUSTOMER PARENT ID', 'CUSTOMER PARENT NM', 'CUSTOMER ID', 'Total Spend', 'Invoice #', 'Shipped Date'],
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

            // if(array_values($supplierValues) === array_values($cleanedArray)){
           
            if (empty(array_diff($supplierValues, $cleanedArray))) {
                /** Get the authenticated user */
                $user = Auth::user();
                $endDateRange = $request->input('enddate');
                if(!empty($endDateRange)){
                    // Split the date range string into start and end dates
                    list($startDate, $endDate) = explode(' - ', $endDateRange);
                    // Convert the date strings to the 'YYYY-MM-DD' format
                    $formattedStartDate = Carbon::createFromFormat('m/d/Y', $startDate)->format('Y-m-d');
                    $formattedEndDate = Carbon::createFromFormat('m/d/Y', $endDate)->format('Y-m-d');
                }
                try{
                    UploadedFiles::create([
                        'supplier_id' => $request->supplierselect,
                        'cron' => UploadedFiles::UPLOAD,
                        'start_date' => $formattedStartDate??"",
                        'end_date' => $formattedEndDate??"",
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
                // $suppliers->id, 
                $suppliers->supplier_name,
                $suppliers->created_at->format('m/d/Y'),
            ];
        }
       
        $data=json_encode($formattedData);
        return view('admin.supplier',compact('data'));
    }
    

    public function deleteFile(Request $request, $id) {
        if (!isset($id)) {
            $id = $request->id;
        }

        try {
            /** Selecting the file data row using table id */
            $fileData = UploadedFiles::where('id',$id)->first();
            $fileData->delete = 1;
            
            if (auth()->check()) {
                $fileData->deleted_by = auth()->user()->id;
            }
            
            $fileData->save();
        } catch (QueryException $e) {   
            Log::error('Database deletion failed:: ' . $e->getMessage());

            /** Error message */
            session()->flash('error', $e->getMessage());
        }
        
        /** Success message */
        session()->flash('success', 'File Successfully Added Delete Quey.');
        return redirect()->back(); 
    }

    public function downloadSampleFile(Request $request, $id=null) {
        if ($id != null) {
            $id = $request->id;
        }

        $filename = [
            1 => 'g_and_t_laboratories_sample.xlsx',
            2 => 'grainger_sample.xlsx',
            3 => 'od_sample.xlsx',
            4 => 'staple_sample.xlsx',
            5 => 'wb_sample.xlsx',
            6 => 'lyreco_sample.xlsx',
            7 => 'od_sample.xlsx',
        ];

        $destinationPath = public_path('/excel_sheets');

        // Set the response headers
        $headers = [
            'Content-Type' => 'application/xlsx',
            'Content-Disposition' => 'attachment; filename="'.$filename[$id].'"',
        ];
        
        return response()->download($destinationPath.'/'.$filename[$id], $filename[$id], $headers);
    }
}