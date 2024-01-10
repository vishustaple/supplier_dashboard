<?php

namespace App\Http\Controllers;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\DB;
use App\Imports\YourImportClass;
use App\Models\CategorySupplier;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx; 


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
        $spreadsheet = $reader->load($request->file('file')); 
        $worksheet = $spreadsheet->getActiveSheet();
        
        // Get the highest row and column numbers
        $highestRow = $worksheet->getHighestRow();
        $highestColumn = $worksheet->getHighestColumn();
        
        // Convert the column letter to a number
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn) - 1;
    
        // Variables to store information about the row with the highest number of columns
        $worksheet_arr = $worksheet->toArray(); 

        $start_index_value_array = $value_array_key = $maxNonEmptyCount = 0;
        foreach ($worksheet_arr as $key=>$value) {
            //Checking not empty columns
            $nonEmptyCount = count(array_filter(array_values($value), function ($item) {
                return !empty($item);
            }));
            
            // if column count is greater then previous row columns count. Then assigen value to '$maxNonEmptyvalue'
            if ($nonEmptyCount == $highestColumnIndex) {
                $maxNonEmptyCount = $nonEmptyCount;
                $maxNonEmptyvalue = $value;
                $start_index_value_array = $key;
                break;
            }
        }

            /** Remove empty key from the array of excel sheet column name */
            $finalExcelKeyArray = array_values(array_filter($maxNonEmptyvalue, function ($item) {
                return !empty($item);
            }, ARRAY_FILTER_USE_BOTH));
            echo"<pre>";
            print_r($finalExcelKeyArray);
            die;
            $suppliers=[
                      '1'=>[],
                      '2' => [
                          'Track Code', 'Track Code Name', 'Sub track Code', 'Sub Track Code Name',
                          'Account Number', 'Account Name', 'Material', 'Material Description',
                          'Material Segment', 'Brand Name', 'Bill Date', 'Billing Document',
                          'Purchase Order Number', 'Sales Document', 'Name of Orderer', 'Sales Office',
                          'Sales Office Name', 'Bill Line No. ', 'Active Price Point', 'Billing Qty',
                          'Purchase Amount', 'Freight Billed', 'Tax Billed', 'Total Invoice Price',
                          'Actual Price Paid', 'Reference Price', 'Ext Reference Price', 'Diff $',
                          'Discount %', 'Invoice Number'
                      ],
                      '3'=>['CUSTOMER GRANDPARENT ID','CUSTOMER GRANDPARENT NM','CUSTOMER PARENT ID','CUSTOMER PARENT NM','CUSTOMER ID','CUSTOMER NM','DEPT','CLASS','SUBCLASS','SKU','Manufacture Item#','Manufacture Name','Product Description','Core Flag','Maxi Catalog/Wholesale Flag','UOM','PRIVATE BRAND','GREEN SHADE','QTY Shipped','Unit Net Price','(Unit) Web Price','Total Spend','Shipto Location','Contact Name','Shipped Date','Invoice #','Payment Method'],
          
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
                          'Customer Num', 'Customer Name', 'Item Num', 'Item Name',
                          'Category', 'Category Umbrella', 'Price Method', 'Uo M',
                          'Current List', 'Qty', 'Ext Price', null
                      ],
                  ];

    return redirect()->back()->with('success', 'Excel file imported successfully!');

    }
}