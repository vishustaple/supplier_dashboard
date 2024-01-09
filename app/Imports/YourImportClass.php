<?php

namespace App\Imports;
use App\Models\UploadedFiles;
use Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

use Maatwebsite\Excel\Events\BeforeSheet;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Reader;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class YourImportClass implements ToCollection //, WithHeadingRow , WithStartRow

{
    private $sheetNames = [];
    private $supplierId;
    private $fileName;
    private $destinationPath;

   

    public function __construct($supplierId, $fileName, $destinationPath)
    {
        $this->supplierId = $supplierId;
        $this->fileName = $fileName;
        $this->destinationPath = $destinationPath;
    }

    public function registerEvents(): array
    {
        return [
            BeforeSheet::class => function (BeforeSheet $event) {
                $this->sheetNames[] = $event->sheet->getTitle();
            },
            AfterImport::class => function (AfterImport $event) {
          
            },
        ];
    }

    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)
    {
        
        $suppliers=[
            '1'=>['invoicenumber','dsdjs'],
            '2' => [
                'Track Code', 'Track Code Name', 'Sub track Code', 'Sub Track Code Name',
                'Account Number', 'Account Name', 'Material', 'Material Description',
                'Material Segment', 'Brand Name', 'Bill Date', 'Billing Document',
                'Purchase Order Number', 'Sales Document', 'Name of Orderer', 'Sales Office',
                'Sales Office Name', 'Bill Line No. ', 'Active Price Point', 'Billing Qty',
                'Purchase Amount', 'Freight Billed', 'Tax Billed', 'Total Invoice Price',
                'Actual Price Paid', 'Reference Price', 'Ext Reference Price', 'Diff $',
                'Discount %', 'Invoice Number', null
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

        // Replace this with the desired supplier ID

        if (isset($suppliers[$this->supplierId])) {
            $supplierValues = $suppliers[$this->supplierId];
            // print_r($supplierValues);
        } else {
            echo "Supplier ID $this->supplierId not found in the array.";
        }
      
        
         // Definig the variable for loop use
         $start_index_value_array = $value_array_key = $maxNonEmptyCount = 0;
         foreach ($collection as $key=>$value) {
            //Checking not empty columns
           $nonEmptyCount = $value->filter(function ($item) {
               return !empty($item);
           })->count();
          // if column count is greater then previous row columns count. Then assigen value to '$maxNonEmptyvalue'
           if ($nonEmptyCount > $maxNonEmptyCount) {
               $maxNonEmptyCount = $nonEmptyCount;
               $maxNonEmptyvalue = $value;
               $start_index_value_array = $key;
           }
       }
       $excel_column_name_array = $maxNonEmptyvalue->toArray();
       if($supplierValues == $excel_column_name_array){
            dd("you have uploaded right file  ");
       }
       else{
        dd("you have uploaded wrong file  ");
       }

        UploadedFiles::create(['supplier_id' => $this->supplierId,
        'file_name' => $this->fileName,
        'file_path' => $this->destinationPath,
        'cron' => 1,]); 
    }
    
    protected function calculateMaxColumn(array $row)
    {
        // This is a simple example; adjust as needed based on your data
        return count($row);
    }
}
