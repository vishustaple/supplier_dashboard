<?php

namespace App\Http\Controllers;

use DB;
use Validator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;
use App\Helpers\ArrayHelper;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx; 
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use App\Models\{CategorySupplier, UploadedFiles, ManageColumns, Order};


class ExcelImportController extends Controller
{
    public function index(){
        $categorySuppliers = CategorySupplier::where('show', 0)->where('show', '!=', 1)->get();

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

            if (isset($item->deleted_at) && !empty($item->deleted_at)) {
                $cronString = 'Deleted';
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

        $pageTitle = "Upload Sheets";
        $data=json_encode($formattedData);
 
        return view('admin.export',compact('categorySuppliers','data', 'pageTitle'));
    }
    public function import(Request $request)
    {
        // dd($request->all());
        ini_set('memory_limit', '1024M');

        $supplierId = $request->supplierselect;
        $supplierFilesNamesArray = [
            1 => 'Usage By Location and Item',
            2 => 'Invoice Detail Report',
            4 => 'All Shipped Order Detail',
            5 => 'Centerpoint_Summary_Report',
            6 => 'Blad1',
            7 => 'Weekly Sales Account Summary', 
        ];

        $suppliers= ManageColumns::getRequiredColumns();
        // $suppliers=[
        //     '1' => ['SOLD TO NAME', 'SOLD TOACCOUNT', 'ON-CORESPEND', 'OFF-CORESPEND'],
        //     '2' => ['Track Code', 'Track Code Name', 'Sub track Code', 'Sub Track Code Name', 'Account Name', 'Account Number', 'Actual Price Paid', 'Invoice Number', 'Bill Date'],
        //     '3' => ['CUSTOMER NM', 'CUSTOMER GRANDPARENT ID', 'CUSTOMER GRANDPARENT NM', 'CUSTOMER PARENT ID', 'CUSTOMER PARENT NM', 'CUSTOMER ID', 'Total Spend', 'Invoice #', 'Shipped Date'],
        //     '4' => ['MASTER_CUSTOMER', 'MASTER_NAME', 'ADJGROSSSALES', 'INVOICENUMBER', 'INVOICEDATE'],
        //     // '5' => ['Customer Name', 'Customer Num', 'Current List', 'Invoice Num', 'Invoice Date'],
        //     '5' => ['Customer Name', 'Customer Num', 'Current List'],
        //     '6' => ['Leader customer 2', 'Leader customer 3', 'Leader customer 4', 'Leader customer 5', 'Leader customer 6', 'Leader customer 1', 'Sales Amount - P', 'Billing Document', 'Billing Date'],
        //     '7'=>  ['GP ID', 'GP Name', 'Parent Id', 'Parent Name', 'Account ID', 'Account Name'],
        //     '8' => ['CUSTOMER NM', 'CUSTOMER GRANDPARENT ID', 'CUSTOMER GRANDPARENT NM', 'CUSTOMER PARENT ID', 'CUSTOMER PARENT NM', 'CUSTOMER ID', 'Total Spend', 'Invoice #', 'Shipped Date'],
        // ];


        $endDateRange = $request->input('enddate');

        /** Split the date range string into start and end dates */
        if(!empty($endDateRange )){
            list($startDate, $endDate) = explode(' - ', $endDateRange);
            
            /** Convert the date strings to the 'YYYY-MM-DD' format */
            $formattedStartDate = Carbon::createFromFormat('m/d/Y', $startDate)->format('Y-m-d');
            $formattedEndDate = Carbon::createFromFormat('m/d/Y', $endDate)->format('Y-m-d');
        }
        
        
        /** Validate the uploaded file */
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
        

        if( $validator->fails() ){  
            $categorySuppliers = $categorySuppliers = CategorySupplier::where('show', 0)->get();
            // return redirect()->back()->withErrors($validator)->withInput(compact('categorySuppliers'));
            return response()->json(['error' => $validator->errors(), 'categorySuppliers' => $categorySuppliers], 200);
        }
        
        try{
            $reader = new Xlsx(); 
            $spreadSheet = $reader->load($request->file('file'), 2);

            if ($supplierId != 3) {
                $sheet = $spreadSheet->getSheetByName($supplierFilesNamesArray[$supplierId]);
            }

            $validationCheck = $arrayDiff = false;
    
            $columnValues = DB::table('manage_columns')->select('id', 'supplier_id', 'field_name')->where('supplier_id', $request->supplierselect)->get();
    
            foreach ($columnValues as $key => $value) {
                if (in_array($value->id, [24, 68, 103, 128, 195, 258])) {
                    $columnArray[$value->supplier_id]['invoice_date'] = $value->field_name;
                }
    
                if (in_array($value->supplier_id, [7])) {
                    $columnArray[$value->supplier_id]['invoice_date'] = '';
                }
            }
            
            // echo"<pre>";
            // print_r($columnArray);
            // die;

           
            if (isset($sheet) && $sheet) {
                $workSheet = $sheet;
                $workSheetArrays = $workSheetArray = $workSheet->toArray();
        
                $maxNonEmptyCount = 0;
        
                foreach ($workSheetArray as $key=>$value) {
                    /** Checking not empty columns */
                    $nonEmptyCount = count(array_filter(array_values($value), function ($item) {
                        return !empty($item);
                    }));
                    
                    /** If column count is greater then previous row columns count. Then assigen value to '$maxNonEmptyvalue' */
                    if ($nonEmptyCount > $maxNonEmptyCount) {
                        $maxNonEmptyvalues = $maxNonEmptyvalue = $value;
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
                            
                /** Clean up the values */
                $cleanedArray = array_map(function ($value) {
                    /** Remove line breaks and trim whitespace */
                    return str_replace(["\r", "\n"], '', $value);
                }, $finalExcelKeyArray);

                $chunkSize = 0; // Adjust as needed
                $dates=[];

                
                if ($request->supplierselect == 7) {
                    foreach ($cleanedArray as $key => $value) {
                        if ($key > 5) {
                            $cleanedArray[$key] = trim("Year_" . substr($cleanedArray[$key], - 2));
                        }
                    }
                }

                foreach ($workSheetArrays as $key => $row) {
                    if (!empty($columnArray[$request->supplierselect]['invoice_date'])) {
                        $keyInvoiceDate = array_search($columnArray[$request->supplierselect]['invoice_date'], $cleanedArray);
                    }

                    if ($request->supplierselect == 2) {
                        $startIndex = $startIndexValueArray + 1;
                    } else {
                        $startIndex = $startIndexValueArray;
                    }
        
                    if($key > $startIndex){
                        if (!empty($keyInvoiceDate) && !empty($row[$keyInvoiceDate])) {
                            if ($row[$keyInvoiceDate] && $request->supplierselect == 4) {
                                $dates[] = date_format(date_create($row[$keyInvoiceDate]),'Y-m-d');
                            } else {
                                $dates[] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[$keyInvoiceDate]))->format('Y-m-d');
                            }
                        } else {
                            $dates[] = '';
                        }
                        
                        if ($chunkSize == 1000) {
                            $fileExist = Order::where(function ($query) use ($dates) {
                                foreach ($dates as $startDate) {
                                    if (!empty($startDate)) {
                                        $query->orWhere('date', '>=', $startDate);
                                    }
                                }
                            })->where('supplier_id', $request->supplierselect);
        
                            if ($fileExist->count() > 0) {
                                // break;
                                return response()->json(['error' => "You have already uploaded this file."], 200);
                            }

                            unset($dates);
                            $chunkSize = 0;
                        }
        
                        $chunkSize++; 
                    }
                }

                $fileExist = Order::where(function ($query) use ($dates) {
                    foreach ($dates as $startDate) {
                        if (!empty($startDate)) {
                            $query->orWhere('date', '>=', $startDate);
                        }
                    }
                })->where('supplier_id', $request->supplierselect);

                if ($fileExist->count() > 0) {
                    return response()->json(['error' => "You have already uploaded this file."], 200);
                }

                if (isset($suppliers[$request->supplierselect])) {
                    $supplierValues = $suppliers[$request->supplierselect];
                    $arrayDiff = array_diff($supplierValues, $cleanedArray);
                    if (empty($arrayDiff)) {
                        $validationCheck = true;
                    }
                }
            } else {
                // $sheetName = $workSheet->getTitle();
                $sheetCount = $spreadSheet->getSheetCount();
                 
                $skipSheet = $sheetCount - 1;
                

                for ($i=0; $i < $sheetCount; $i++) {
                    $workSheet = $spreadSheet->getSheet($i);
        
                    $workSheetArrays = $workSheetArray1 = $workSheet->toArray();

                    $maxNonEmptyCount = 0;
            
                    foreach ($workSheetArray1 as $key=>$value) {
                        /** Checking not empty columns */
                        $nonEmptyCount = count(array_filter(array_values($value), function ($item) {
                            return !empty($item);
                        }));
                        
                        /** If column count is greater then previous row columns count. Then assigen value to '$maxNonEmptyvalue' */
                        if ($nonEmptyCount > $maxNonEmptyCount) {
                            $maxNonEmptyvalues = $maxNonEmptyvalue1 = $value;
                            $startIndexValueArray = $key;
                            $maxNonEmptyCount = $nonEmptyCount;
                        } 
                        
                        /** Stop loop after reading 31 rows from excel file */
                        if($key > 20){
                            break;
                        }
                    }
        
                    /** Remove empty key from the array of excel sheet column name */
                    $finalExcelKeyArray1 = array_values(array_filter($maxNonEmptyvalue1, function ($item) {
                        return !empty($item);
                    }, ARRAY_FILTER_USE_BOTH));
                                
                    /** Clean up the values */
                    $cleanedArray = array_map(function ($value) {
                        /** Remove line breaks and trim whitespace */
                        return str_replace(["\r", "\n"], '', $value);
                    }, $finalExcelKeyArray1);

                    if ($request->supplierselect == 7) {
                        foreach ($cleanedArray as $key => $value) {
                            if ($key > 5) {
                                $cleanedArray[$key] = trim("Year_" . substr($cleanedArray[$key], - 2));
                            }
                        }
                    }

                    if ($request->supplierselect == 2) {
                        $startIndex = $startIndexValueArray + 1;
                    } else {
                        $startIndex = $startIndexValueArray;
                    }

                    $chunkSize = 0; // Adjust as needed
                    $dates = [];
                    if (!empty($columnArray[$request->supplierselect]['invoice_date'])) {
                        $keyInvoiceDate = array_search($columnArray[$request->supplierselect]['invoice_date'], $cleanedArray);
                    }
                    if (!empty($keyInvoiceDate)) {
                        foreach ($workSheetArrays as $key => $row) {
                            if($key > $startIndex){
                                if (!empty($row[$keyInvoiceDate])) {
                                    if ($row[$keyInvoiceDate] && $request->supplierselect == 4) {
                                        $dates[] = date_format(date_create($row[$keyInvoiceDate]),'Y-m-d');
                                    } else {
                                        $dates[] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[$keyInvoiceDate]))->format('Y-m-d');
                                    }

                                    if ($chunkSize == 1000) {
                                        $fileExist = Order::where(function ($query) use ($dates) {
                                            foreach ($dates as $startDate) {
                                                if (!empty($startDate)) {
                                                    $query->orWhere('date', '>=', $startDate);
                                                }
                                            }
                                        })->where('supplier_id', $request->supplierselect);
        
                                        if ($fileExist->count() > 0) {
                                            return response()->json(['error' => "You have already uploaded this file."], 200);
                                            // break;
                                        }
                                    
                                        $chunkSize = 0;
                                    }
                                } else {
                                    $dates = [];
                                }
                
                                $chunkSize++;
                            }
                        }

                        if (!empty($dates)) {
                            $fileExist = Order::where(function ($query) use ($dates) {
                                foreach ($dates as $startDate) {
                                    if (!empty($startDate)) {
                                        $query->orWhere('date', '>=', $startDate);
                                    }
                                }
                            })->where('supplier_id', $request->supplierselect);
                    
                            if ($fileExist->count() > 0) {
                                return response()->json(['error' => "You have already uploaded this file."], 200);
                            }
                        }
                    }
                    
                    if (isset($suppliers[$request->supplierselect])) {
                        $supplierValues = $suppliers[$request->supplierselect];
                        $arrayDiff = array_diff($supplierValues, $cleanedArray);

                        if (empty($arrayDiff)) {
                            $validationCheck = true;
                            break;
                        }
                    }
                }
            }

        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        if ($validationCheck == false) {
            $missingColumns = implode(', ', $arrayDiff);
            return response()->json(['error' => "We're sorry, but it seems the file you've uploaded does not meet the required format. Following ".$missingColumns." columns are missing in uploaded file"], 200);
        }
      

        /** Output the cleaned array */
        // echo"<pre>";
        // print_r($cleanedArray);
        // $clean= ManageColumns::cleanRows($cleanedArray);
        // print_r($clean);
        // die;

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
            }
            return response()->json(['success' => 'Excel file imported successfully!'], 200);
        } else {
            return response()->json(['error' => 'Please select supplier.'], 200);
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
                $suppliers->created_at ? $suppliers->created_at->format('m/d/Y') : 'null', 
                // $suppliers->created_at->format('m/d/Y'),
            ];
        }
       
        $data=json_encode($formattedData);
        $pageTitle = 'Supplier Data';
        return view('admin.supplier',compact('data', 'pageTitle'));
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

        /** Set the response headers */
        $headers = [
            'Content-Type' => 'application/xlsx',
            'Content-Disposition' => 'attachment; filename="'.$filename[$id].'"',
        ];
        
        return response()->download($destinationPath.'/'.$filename[$id], $filename[$id], $headers);
    }

    public function getColumns(Request $request){
        $columns = ManageColumns::where('supplier_id',$request->dataIdValue)->get();
        return response()->json($columns);
    }
    public function saveColumns(Request $request){
        foreach ($request->all() as $key => $value) {
            $id = $value['fieldId'];
            $columnValue = $value['fieldValue'];
            $column = ManageColumns::find($id);
            if ($column) {
                $column->field_name = $columnValue;
                $column->save();
            } 
        }
        return response()->json(['status' => 'success', 'message' => 'Column value updated successfully'], 200);

    }
}