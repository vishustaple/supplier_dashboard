<?php

namespace App\Http\Controllers;
use Excel;
use Illuminate\Http\Request;
use Validator;
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
          if( $validator->fails() )
          {  
            $categorySuppliers = CategorySupplier::all();
              return view('admin.export',compact('categorySuppliers'))->withErrors($validator); 
          }

          $reader = new Xlsx(); 
          $spreadsheet = $reader->load($request->file('file')); 
          $worksheet = $spreadsheet->getActiveSheet();  
          
          $highestRow = $worksheet->getHighestRow();
          $highestColumn = $worksheet->getHighestColumn();
          $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

          // Get data from the row with the highest index
          $lastRowData = [];

          for ($col = 1; $col <= $highestColumnIndex; $col++) {
          $cellValue = $worksheet->getCellByColumnAndRow($col, $highestRow)->getValue();
          dd($cellValue);
          // Store the cell value in the $lastRowData array
          $lastRowData[] = $cellValue;
          }

          // Now $lastRowData contains the data from the row with the highest index
          // You can use or manipulate this data as needed
          print_r($lastRowData);

        //   dd($highestColumnIndex);
          // Array to store headers with values
        //   dd($highestColumn);
          $worksheet_arr = $worksheet->toArray(); 
        // Get the uploaded file
        $file = $request->file('file');

        // Generate a timestamp to append to the file name
        $timestamp = now()->format('YmdHis');

        // Original file name
        $originalFileName = $file->getClientOriginalName();

        // Append timestamp to the file name
        $fileName = $timestamp . '_' . $originalFileName;

        // Define the folder where you want to save the file
        $destinationPath = public_path('/excel_sheets');

      
        // Move the file with the new name
        $file->move($destinationPath, $fileName);
       
          //test the file 
          Excel::import(new YourImportClass($supplierId, $fileName, $destinationPath), $destinationPath . '/' . $fileName);

    
    return redirect()->back()->with('success', 'Excel file imported successfully!');

    }
}