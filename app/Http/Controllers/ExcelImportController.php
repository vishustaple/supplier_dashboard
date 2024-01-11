<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
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

        /** Validate the uploaded file */
        $validator = Validator::make(['supplierselect' => $request->supplierselect,'file' => $request->file('file')],
            ['supplierselect'=>'required', 'file' => 'required|file|mimes:xlsx,xls'],
            ['supplierselect.required' => 'Please select a supplier. It is a required field.']
        );

        if( $validator->fails() ){  
            $categorySuppliers = CategorySupplier::all();
            return view('admin.export',compact('categorySuppliers'))->withErrors($validator); 
        }
        
        $reader = new Xlsx(); 
        $spreadSheet = $reader->load($request->file('file')); 
        $workSheet = $spreadSheet->getActiveSheet();
    
        /** Variables to store information about the row with the highest number of columns */
        $workSheetArray = $workSheet->toArray(); 

        $startIndexValueArray = $valueArrayKey = $maxNonEmptyCount = 0;
        foreach ($workSheetArray as $key=>$value) {
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

        // echo"<pre>";
        // print_r($finalExcelKeyArray);
        // die;
        return redirect()->back()->with('success', 'Excel file Uploaded successfully. System take 30 minute to process it.');

    }
}