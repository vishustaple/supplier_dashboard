<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\Models\CategorySupplier;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx; 
use PhpOffice\PhpSpreadsheet\IOFactory;


class ExcelImportController extends Controller
{
    public function index(){
        $categorySuppliers = CategorySupplier::all();
        return view('admin.export',compact('categorySuppliers'));
    }
    public function import(Request $request)
    {
        $supplierId=$request->supplierselect;
    
        // Validate the uploaded file
        $validator = Validator::make(
            [
                'supplierselect'=>$request->supplierselect,
                'file'      =>  $request->file('file'),
            ],
            [
                'supplierselect'=>'required',
                'file'          => 'required|file|mimes:xlsx,xls',
            ],
            [
                'supplierselect.required' => 'Please select a supplier. It is a required field.',
            ]
        );

        if( $validator->fails() ){  
            $categorySuppliers = CategorySupplier::all();
            return view('admin.export',compact('categorySuppliers'))->withErrors($validator); 
        }
        
        $reader = new Xlsx(); 
        // $excelFilePath = $request->file('file')->getPathname();
        $spreadSheet = $reader->load($request->file('file'), 2);
        // $spreadsheet = IOFactory::load( $excelFilePath);
        // $spreadsheet->setReadDataOnly(true);
        // try {
        //     $spreadsheet = IOFactory::load($excelFilePath);
        //     $spreadsheet->setReadDataOnly(true);
        // } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
        //     die('Error loading the Excel file: ' . $e->getMessage());
        // }
        // $spreadSheet = $reader->load($request->file('file')); 
        $sheetCount = $spreadSheet->getSheetCount();
        // print_r($sheetCount);die;
        $workSheet = $spreadSheet->getActiveSheet();

        /** Variables to store information about the row with the highest number of columns */
        $workSheet_arr = $workSheet->toArray(); 

        $startIndexValueArray = $valueArrayKey = $maxNonEmptyCount = 0;
        foreach ($workSheet_arr as $key=>$value) {
            /**Checking not empty columns */
            $nonEmptyCount = count(array_filter(array_values($value), function ($item) {
                return !empty($item);
            }));
            
            /** if column count is greater then previous row columns count. Then assigen value to '$maxNonEmptyvalue' */
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
            // Clean up the values
            $cleanedArray = array_map(function ($value) {
            // Remove line breaks and trim whitespace
            return str_replace(["\r", "\n"], '', $value);
            }, $finalExcelKeyArray);

            // Output the cleaned array
            // echo"<pre>";
            // dd($cleanedArray);
            // die;
            $suppliers=[
                      '1'=>[  
                      'SOLD TOACCOUNT','SOLD TO NAME','SHIP TOACCOUNT','SHIP TO NAME','SHIP TO ADDRESS','CATEGORIES','SUB GROUP 1','PRODUCT','DESCRIPTION','GREEN (Y/N)','QUANTITYSHIPPED','ON-CORESPEND','OFF-CORESPEND'],
                      '2' => [
                          'Track Code', 'Track Code Name', 'Sub track Code', 'Sub Track Code Name','Account Number', 'Account Name', 'Material', 'Material Description','Material Segment', 'Brand Name', 'Bill Date', 'Billing Document','Purchase Order Number', 'Sales Document', 'Name of Orderer', 'Sales Office','Sales Office Name', 'Bill Line No. ', 'Active Price Point', 'Billing Qty','Purchase Amount', 'Freight Billed', 'Tax Billed', 'Total Invoice Price','Actual Price Paid', 'Reference Price', 'Ext Reference Price', 'Diff $','Discount %', 'Invoice Number'
                      ],
                      '3'=>['CUSTOMER GRANDPARENT ID','CUSTOMER GRANDPARENT NM','CUSTOMER PARENT ID','CUSTOMER PARENT NM','CUSTOMER ID','CUSTOMER NM','DEPT','CLASS','SUBCLASS','SKU','Manufacture Item#','Manufacture Name','Product Description','Core Flag','Maxi Catalog/WholesaleFlag','UOM','PRIVATE BRAND','GREEN SHADE','QTY Shipped','Unit Net Price','(Unit) Web Price','Total Spend','Shipto Location','Contact Name','Shipped Date','Invoice #','Payment Method'],
          
                      '4' => [
                          'MASTER_CUSTOMER', 'MASTER_NAME', 'BILLTONUMBER', 'BILLTONAME', 'SHIPTONUMBER', 'SHIPTONAME',
                          'SHIPTOADDRESSLINE1', 'SHIPTOADDRESSLINE2', 'SHIPTOADDRESSLINE3', 'SHIPTOCITY', 'SHIPTOSTATE',
                          'SHIPTOZIPCODE', 'LASTSHIPDATE', 'SHIPTOCREATEDATE', 'SHIPTOSTATUS', 'LINEITEMBUDGETCENTER',
                          'CUSTPOREL', 'CUSTPO', 'ORDERCONTACT', 'ORDERCONTACTPHONE', 'SHIPTOCONTACT', 'ORDERNUMBER',
                          'ORDERDATE', 'SHIPPEDDATE', 'TRANSSHIPTOLINE3', 'SHIPMENTNUMBER', 'TRANSTYPECODE',
                          'ORDERMETHODDESC', 'PYMTTYPE', 'PYMTMETHODDESC', 'INVOICENUMBER', 'SUMMARYINVOICENUMBER',
                          'INVOICEDATE', 'CVNCECARDFLAG', 'SKUNUMBER', 'ITEMDESCRIPTION', 'STAPLESADVANTAGEITEMDESCRIPTION',
                          'SELLUOM', 'QTYINSELLUOM', 'STAPLESOWNBRAND', 'DIVERSITYCD', 'DIVERSITY', 'DIVERSITYSUBTYPECD',
                          'DIVERSITYSUBTYPE', 'CONTRACTFLAG', 'SKUTYPE', 'TRANSSOURCESYSCD', 'TRANSACTIONSOURCESYSTEM',
                          'ITEMFREQUENCY', 'NUMBERORDERSSHIPPED', 'QTY', 'ADJGROSSSALES', 'AVGSELLPRICE'
                      ],
                      '5' => [
                        'Sales ID','Customer Num','Customer Name','Invoice Num','Invoice Date','PONumber','Cost Center Code','Cost Center Value','Dlv Name','Dlv Street','Dlv City','Dlv State','Dlv Zip','Item Num','Item Name','Category','Category Umbrella','Price Method','Uo M','Current List','Qty','Price','Ext Price','Line Tax','Line Total',
                      ],
                      '6'=>[  'Payer', 'Name Payer', 'Sold-to pt', 'Name Sold-to party',
                      'Ship-to', 'Name Ship-to', 'Name 3 + Name 4 - Ship-to',
                      'Street - Ship-to', 'District - Ship-to', 'PostalCode - Ship-to',
                      'City - Ship-to', 'Country - Ship-to', 'Leader customer 1',
                      'Leader customer 2', 'Leader customer 3', 'Leader customer 4',
                      'Leader customer 5', 'Leader customer 6', 'Product hierarchy',
                      'Section', 'Family', 'Category', 'Sub Category', 'Material',
                      'Material Description', 'Ownbrand', 'Green product', 'NBS',
                      'Customer Material', 'Customer description', 'Sales unit',
                      'Qty. in SKU', 'Sales deal', 'Purchase order type',
                      'Qty in Sales Unit - P', 'Quantity in SKU - P', 'Number of orders - P',
                      'Sales Amount - P', 'Tax amount - P', 'Net sales - P',
                      'Avg Selling Price - P', 'Document Date', 'Sales Document',
                      'PO number', 'BPO number', 'Invoice list', 'Billing Document',
                      'Billing Date', 'CAC number', 'CAC description', 'Billing month - P'],
                  ];
                  //check supllier upload right file or not   
                  if (isset($suppliers[$supplierId])) {
                  
                    $supplierValues = $suppliers[$supplierId];


                    if(array_values($supplierValues) === array_values($finalExcelKeyArray)){
                  
                      return redirect()->back()->with('success', 'Excel file imported successfully!');
                    }
                    else{
                      
                      return redirect()->back()->with('error', 'Please upload a file that corresponds to the selected supplier.');
                    }
                } else {
                    echo "Supplier ID $this->supplierId not found in the array.";
                }
     

    // return redirect()->back()->with('success', 'Excel file imported successfully!');

    }
}