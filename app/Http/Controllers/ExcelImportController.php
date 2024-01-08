<?php

namespace App\Http\Controllers;
use Excel;
use Illuminate\Http\Request;
use Validator;
use App\Imports\YourImportClass;



class ExcelImportController extends Controller
{
    public function index(){
       
        return view('admin.export');
    }
    public function import(Request $request)
    {
        
        // Validate the uploaded file
        $validator = Validator::make(
            [
                'file'      => $request->file,
                'extension' => strtolower($request->file->getClientOriginalExtension()),
            ],
            [
                // 'file'          => 'required',
                'extension'      => 'required|in:xlsx,xls',
            ]
          
          );
          if( $validator->fails() )
          {  
           
              
              return view('admin.export')->withErrors($validator); 
              
          }


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

        // $fileName now contains the timestamped file name
        dd($fileName);
        // $path = $file->store('uploads');
        // dd($path);
        // $fullPath = storage_path("app/{$path}");
   
        // $folderPath = public_path('/excel_files'); // You can change this path as needed
        // // dd($folderPath);
        // // Move the file to the specified folder
        // $filePath = $file->storeAs($folderPath, $file->getClientOriginalName());
     
        // Process the Excel file
 
    Excel::import(new YourImportClass, $destinationPath . '/' . $fileName);
    return redirect()->back()->with('success', 'Excel file imported successfully!');

    }
}