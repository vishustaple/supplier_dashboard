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
        $fileName= $file->getClientOriginalName();
        // Define the folder where you want to save the file
        $destinationPath = public_path('/excel_sheets');
   
        $file->move($destinationPath, $file->getClientOriginalName());
     
         // Specify the chunk size (number of rows per chunk)
         $chunkSize = 100; // Adjust this based on your needs

         // Use the chunk method to process the file in chunks
         Excel::filter('chunk')->load($destinationPath . '/' . $fileName)->chunk($chunkSize, function ($results) {
             // Process each chunk using the import class
             Excel::import(new YourImport, $results);
         });
     
        // Process the Excel file
 
    // Excel::import(new YourImportClass, $destinationPath . '/' . $fileName);
    return redirect()->back()->with('success', 'Excel file imported successfully!');

    }
}