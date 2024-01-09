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
          dd($worksheet);
          $worksheet_arr = $worksheet->toArray(); 
        // Get the uploaded file
        $file = $request->file('file');

        // Generate a timestamp to append to the file name
        $timestamp = now()->format('YmdHis');

        // Append timestamp to the file name
        $fileName = $timestamp . '_' . $file->getClientOriginalName();

        // Define the folder where you want to save the file
        $destinationPath = public_path('/excel_sheets');

      
        // Move the file with the new name
        $file->move($destinationPath, $fileName);
       
          //test the file 
          Excel::import(new YourImportClass($supplierId, $fileName, $destinationPath), $destinationPath . '/' . $fileName);

        // $fileName now contains the timestamped file name
        // dd($fileName);
        // $path = $file->store('uploads');
        // dd($path);
        // $fullPath = storage_path("app/{$path}");
   
        // $file->move($destinationPath, $file->getClientOriginalName());
     
   
    return redirect()->back()->with('success', 'Excel file imported successfully!');

    }
}