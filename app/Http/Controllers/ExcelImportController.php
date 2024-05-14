<?php

namespace App\Http\Controllers;

use Validator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx; 
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use App\Models\{
    Order,
    UploadedFiles,
    ManageColumns,
    SupplierDetail,
    CategorySupplier,
};


class ExcelImportController extends Controller
{
    public function index(){
        $categorySuppliers = CategorySupplier::where('show', 0)->where('show', '!=', 1)->get();
        $uploadData = UploadedFiles::query()->selectRaw("`uploaded_files`.*, CONCAT(`users`.`first_name`, ' ', `users`.`last_name`) AS user_name")
        ->leftJoin('users', 'uploaded_files.created_by', '=', 'users.id')
        ->get();
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
                ((isset($item->user_name)) ? ($item->user_name) : (' ')),
                $item->created_at->format('m/d/Y'),
                (isset($item->delete) && !empty($item->delete)) ? ('<div class="spinner"><div class="bounce1"></div><div class="bounce2"></div><div class="bounce3"></div></div>') : ((isset($item->deleted_at) && !empty($item->deleted_at) ? '<button class="btn btn-danger btn-xs remove invisible" ><i class="fa-solid fa-trash"></i></button>' : '<button data-id="'.$item->id.'" class="btn btn-danger btn-xs remove" title="Remove File"><i class="fa-solid fa-trash"></i></button>')),
            ];
            $i++;
        }
        
        $pageTitle = "Upload Sheets";
        $data=json_encode($formattedData);
 
        return view('admin.export',compact('categorySuppliers','data', 'pageTitle'));
    }
    public function import(Request $request){
        ini_set('memory_limit', '1024M');
        $suppliers = ManageColumns::getRequiredColumns();
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
            ['supplierselect' => $request->supplierselect, 'file' => $request->file('file')],
            ['supplierselect' => 'required', 'file' => 'required|file|mimes:xlsx,xls'],
            ['supplierselect.required' => 'Please select a supplier. It is a required field.']
        );

        if( $validator->fails() ){  
            $categorySuppliers = $categorySuppliers = CategorySupplier::where('show', 0)->get();
            return response()->json(['error' => $validator->errors(), 'categorySuppliers' => $categorySuppliers], 200);
        }
        
        try{
            $reader = new Xlsx(); 
            $spreadSheet = $reader->load($request->file('file'), 2);
            $validationCheck = $arrayDiff = false;
            // $columnValues = DB::table('manage_columns')->select('id', 'supplier_id', 'field_name')->where('supplier_id', $request->supplierselect)->get();
    
            // /** Here we getting date column name form database */
            // foreach ($columnValues as $key => $value) {
            //     if (in_array($value->id, [24, 68, 103, 128, 195, 258])) {
            //         $columnArray[$value->supplier_id]['invoice_date'] = $value->field_name;
            //     }
    
            //     if (in_array($value->supplier_id, [7])) {
            //         $columnArray[$value->supplier_id]['invoice_date'] = '';
            //     }
            // }
                
            foreach ($spreadSheet->getAllSheets() as $spreadSheets) {
                $maxNonEmptyCount = 0;
                foreach ($spreadSheets->toArray() as $key=>$value) {
                    /** Checking not empty columns */
                    $nonEmptyCount = count(array_filter(array_values($value), function ($item) {
                        return !empty($item);
                    }));
                    
                    /** If column count is greater then previous row columns count. Then assigen value to '$maxNonEmptyvalue' */
                    if ($nonEmptyCount > $maxNonEmptyCount) {
                        $maxNonEmptyvalue1 = $value;
                        $startIndexValueArray = $key;
                        $maxNonEmptyCount = $nonEmptyCount;
                    }
                    
                    /** Stop loop after reading 31 rows from excel file */
                    if($key > 2){
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

             

                // $chunkSize = 0; // Adjust as needed
                // $dates = [];

                // if (!empty($columnArray[$request->supplierselect]['invoice_date'])) {
                //     $keyInvoiceDate = array_search($columnArray[$request->supplierselect]['invoice_date'], $cleanedArray);
                // }

                // if (!empty($keyInvoiceDate)) {
                //     foreach ($spreadSheets->toArray() as $key => $row) {
                //         if($key > $startIndex){
                //             /** Here we create dates array which are into the sheet. */
                //             if (!empty($row[$keyInvoiceDate])) {
                //                 if ($request->supplierselect == 4) {
                //                     $date = explode("-", $row[$keyInvoiceDate]);
                //                     if(count($date) <= 2){
                //                         $dates[] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[$keyInvoiceDate]))->format('Y-m-d');
                //                     } else {
                //                         $dates[] = date_format(date_create($row[$keyInvoiceDate]), 'Y-m-d');
                //                     }
                //                 } else {
                //                     $dates[] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[$keyInvoiceDate]))->format('Y-m-d');
                //                 }

                //                 /** After getting 1000 array chunk. We will apply query into this chunk. 
                //                  * And check if data exist then show user and error you have uploaded same file already.
                //                  */
                //                 if ($chunkSize == 1000) {
                //                     $fileExist = Order::where(function ($query) use ($dates) {
                //                         foreach ($dates as $startDate) {
                //                             if (!empty($startDate)) {
                //                                 $query->orWhere('date', '=', $startDate);
                //                             }
                //                         }
                //                     })->where('supplier_id', $request->supplierselect);
                                    
                //                     $chunkSize = 0;

                //                     if ($fileExist->count() > 0) {
                //                         return response()->json(['error' => "You have already uploaded this file."], 200);
                //                     }
                                
                //                 }
                //             } else {
                //                 $dates = [];
                //             }
            
                //             $chunkSize++;
                //         }
                //     }

                //     /** Here we check those dates which are not come into previous if condition */
                //     if (!empty($dates)) {
                //         $fileExist = Order::where(function ($query) use ($dates) {
                //             foreach ($dates as $startDate) {
                //                 if (!empty($startDate)) {
                //                     $query->orWhere('date', '=', $startDate);
                //                 }
                //             }
                //         })->where('supplier_id', $request->supplierselect);
                
                //         if ($fileExist->count() > 0) {
                //             return response()->json(['error' => "You have already uploaded this file."], 200);
                //         }
                //     }
                // }
                
                /** Here we check all required columns of uploaded file match with particuler supplier file columns
                 * If not match all required columns of uploaded file not match with particuler supplier file columns.
                 * Then set $validationCheck true.
                  */
                if (isset($suppliers[$request->supplierselect])) {
                    $supplierValues = $suppliers[$request->supplierselect];
                    $arrayDiff = array_diff($supplierValues, $cleanedArray);

                    if (empty($arrayDiff)) {
                        $validationCheck = true;
                        break;
                    }
                }
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        /** Here we return the error into form of json */
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
        $categorySuppliers = CategorySupplier::select([
            'suppliers.id as id',
            'suppliers.supplier_name as supplier_name',
            'suppliers_detail.first_name as first_name',
            'suppliers_detail.last_name as last_name',
            'suppliers_detail.email as email',
            'suppliers_detail.phone as phone',
            'suppliers_detail.status as status',
            'department.department as department',
        ])
        ->leftJoin('suppliers_detail', function($join) {
            $join->on('suppliers_detail.supplier', '=', 'suppliers.id')
                 ->where('suppliers_detail.main', '=', 1);
        })
        ->leftJoin('department', 'department.id', '=', 'suppliers_detail.department_id')
        ->get();

        // dd($categorySuppliers);

        $formattedData = [];
        foreach ($categorySuppliers as $suppliers) {
            $formattedData[] = [
                '<a class="dots" href="'.route('supplier.show', ['id' => $suppliers->id]).'">'.$suppliers->supplier_name.'</a>',
                $suppliers->department,
                $suppliers->first_name.' '.$suppliers->last_name,
                $suppliers->email,
                $suppliers->phone,
                (($suppliers->status == 1) ? ('Active') : ((isset($suppliers->status)) ? ('In-active') : (''))),
            ];
        }
       
        $data = json_encode($formattedData);
        $pageTitle = 'Supplier Data';
        return view('admin.supplier', compact('data', 'pageTitle'));
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

    public function getExportWithAjax(Request $request){
        if ($request->ajax()) {
            $formatuserdata = UploadedFiles::getFilterdExcelData($request->all());
            return response()->json($formatuserdata);
        }
    }
 
    public function editSupplierName(Request $request) {
        $validator = Validator::make(
            [
                'id' => $request->id,
                'phone' => $request->phone,
                'email' => $request->email,
                'status' => $request->status,
                'last_name' => $request->last_name,
                'department' => $request->department,
                'first_name' => $request->first_name,
                'supplier_id' => $request->supplier_id,
            ],
            [
                'id' => 'required',
                'phone' => 'required',
                'email' => 'required',
                'status' => 'required',
                'last_name' => 'required',
                'first_name' => 'required',
                'department' => 'required',
                'supplier_id' => 'required',
            ],
        );

        if( $validator->fails()){  
            return response()->json(['error' => $validator->errors()], 200);
        }

        if ($request->main == 1) {
            SupplierDetail::where('main', 1)
            ->where('supplier', $request->supplier_id)
            ->update(['main' => 0]);

            $supplierData = SupplierDetail::find($request->id);
            $supplierData->update([
                'main' => $request->main,
                'phone' => $request->phone,
                'email' => $request->email,
                'status' => $request->status,
                'last_name' => $request->last_name,
                'supllier' => $request->supplier_id,
                'first_name' => $request->first_name,
                'department_id' => $request->department,
            ]);
        } else {
            $supplierData = SupplierDetail::find($request->id);
            $supplierData->update([
                'main' => 0,
                'phone' => $request->phone,
                'email' => $request->email,
                'status' => $request->status,
                'last_name' => $request->last_name,
                'supllier' => $request->supplier_id,
                'first_name' => $request->first_name,
                'department_id' => $request->department,
            ]);

            $existRecord = SupplierDetail::where('main', 1)
            ->where('supplier', $request->supplier_id)
            ->first();
            if (!$existRecord) {
                $updateManiRecord = SupplierDetail::where('id', '!=', $request->id)
                ->where('supplier', $request->supplier_id)
                ->first();

                SupplierDetail::where('id', $updateManiRecord->id)
                ->where('supplier', $request->supplier_id)
                ->update(['main' => 1]);
            }
        }

        return response()->json(['success' => 'Supplier info updated'], 200);
    }

    public function addSupplierName(Request $request){
        $validator = Validator::make(
            [
                'phone'=> $request->phone,
                'email'=> $request->email,
                'department'=> $request->department,
                'status'=> $request->status,
                'last_name'=> $request->last_name,
                'first_name'=> $request->first_name,
                'supplier_id' => $request->supplier_id,
            ],
            [
                'department'=>'required',
                'phone'=>'required',
                'email'=>'required',
                'status'=>'required',
                'last_name'=>'required',
                'first_name'=>'required',
                'supplier_id' => 'required',
            ],
        );

        if( $validator->fails()){  
            return response()->json(['error' => $validator->errors()], 200);
        }
        
        if ($request->main == 1) {
            SupplierDetail::where('main', 1)->where('supplier', $request->supplier_id)
            ->update(['main' => 0]);

            SupplierDetail::create([
                'main'=> $request->main,
                'phone' => $request->phone,
                'email' => $request->email,
                'status' => $request->status,
                'last_name' => $request->last_name,
                'supplier' => $request->supplier_id,
                'first_name' => $request->first_name,
                'department_id' => $request->department,
            ]);
        } else {
            SupplierDetail::create([
                'main' => 0,
                'phone' => $request->phone,
                'email' => $request->email,
                'status' => $request->status,
                'last_name' => $request->last_name,
                'supplier' => $request->supplier_id,
                'first_name' => $request->first_name,
                'department_id' => $request->department,
            ]);
        }
        
        return response()->json(['success' => 'Supplier info added'], 200);
    }

    public function addSupplierMain(Request $request) {
        if ($request->main == 1) {
            SupplierDetail::where('main', 1)->where('supplier', $request->supplier_id)
            ->update(['main' => 0]);

            SupplierDetail::where('id', $request->id)->update(['main'=> $request->main]);
        } else {
            SupplierDetail::where('id', $request->id)->update(['main' => 0]);
        }

        return response()->json(['success' => 'Supplier info updated'], 200);
    }

    public function deleteSupplier(Request $request) {
        $checked = SupplierDetail::select('id')->where('id', $request->id)->where('main', 1)->count();
        
        if ($checked > 0) {
            SupplierDetail::where('id', $request->id)->delete();
            $id = SupplierDetail::where('supplier', $request->supplier_id)->first();
            if ($id) {
                SupplierDetail::where('id', $id->id)->update(['main' => 1]);
            }
        } else {
            SupplierDetail::where('id', $request->id)->delete();
        }

        return response()->json(['success' => 'Supplier info deleted'], 200);
    }

    public function showSupplier(Request $request) {
        $id = $request->id;
        $pageTitle = getSupplierName($request->id);
        $departments = DB::table('department')->get();
        return view('admin.supplier_detail',compact('id', 'pageTitle', 'departments'));
    }

    public function getSupplierDetailWithAjax(Request $request){
        if ($request->ajax()) {
            $supplierData = SupplierDetail::getSupplierDetailFilterdData($request->all());
            return response()->json($supplierData);
        }
    }
}