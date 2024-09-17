<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\QueryException;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use App\Models\{
    UploadedFiles,
    ManageColumns,
    SupplierDetail,
    ShowPermissions,
    CategorySupplier,
    RequiredFieldName,
};

class ExcelImportController extends Controller
{
    public function __construct() {
        $this->middleware('permission:Manage Supplier')->only(['allSupplier']);
        $this->middleware('permission:Supplier Edit')->only(['editSupplierName']);
        $this->middleware('permission:Supplier Add')->only(['addSupplierName', 'addSupplierMain']);
        $this->middleware('permission:Supplier Delete')->only(['deleteSupplier']);
    }

    public function index() {
        $categorySuppliers = CategorySupplier::where('show', 0)->where('show', '!=', 1)->get();
        $uploadData = UploadedFiles::query()->selectRaw("`attachments`.*, CONCAT(`users`.`first_name`, ' ', `users`.`last_name`) AS user_name")
        ->leftJoin('users', 'attachments.created_by', '=', 'users.id')
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

    public function import(Request $request) {
        /** Increasing memory for smoothly process data of excel file */
        ini_set('memory_limit', '1024M');

        /** Getting suppliers ids and its required columns */
        $suppliers = ManageColumns::getRequiredColumns();
        
        /** Validate the uploaded file */
        $validator = Validator::make(
            ['supplierselect' => $request->supplierselect, 'file' => $request->file('file')],
            ['supplierselect' => 'required', 'file' => 'required'],
            ['supplierselect.required' => 'Please select a supplier. It is a required field.']
        );

        if( $validator->fails() ){  
            $categorySuppliers = $categorySuppliers = CategorySupplier::where('show', 0)->get();
            return response()->json(['error' => $validator->errors(), 'categorySuppliers' => $categorySuppliers], 200);
        }
        
        try {
            /** Getting the file extension for process file according to the extension */
            $inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($request->file('file'));

            if ($inputFileType === 'Xlsx') {
                $reader = new Xlsx();
            } elseif ($inputFileType === 'Xls') {
                $reader = new Xls();
            } else {
                return response()->json(['error' => 'Unsupported file type: ' . $inputFileType], 200);
                throw new Exception('Unsupported file type: ' . $inputFileType);
            }

            /** Loading the file without attached image */
            $spreadSheet = $reader->load($request->file('file'), 2);

            /** Setting the variables for validation */
            $validationCheck = $arrayDiff = false;
            foreach ($spreadSheet->getAllSheets() as $spreadSheets) {
                if ($validationCheck == true) {
                    break;
                }

                foreach ($spreadSheets->toArray() as $value) {
                    /** Remove empty key from the array of excel sheet column name */
                    $finalExcelKeyArray1 = array_values(array_filter($value, function ($item) {
                        return !empty($item);
                    }, ARRAY_FILTER_USE_BOTH));
                                
                    /** Clean up the values */
                    $cleanedArray = array_map(function ($values) {
                        /** Remove line breaks and trim whitespace */
                        return trim(str_replace(["\r", "\n"], '', $values));
                    }, $finalExcelKeyArray1);

                    /** Handle case of office depot weekly excel file */
                    if ($request->supplierselect == 7) {
                        foreach ($cleanedArray as $keys => $valuess) {
                            if ($keys > 5) {
                                $cleanedArray[$keys] = trim("year_" . substr($cleanedArray[$keys], - 2));
                            }
                        }
                    }

                    if (isset($suppliers[$request->supplierselect])) {
                        $supplierValues = $suppliers[$request->supplierselect];
                       
                        if ($request->supplierselect == 7) {
                            $supplierValues = array_slice($supplierValues, 0, 6, true);
                        }
                        
                        /** Getting the difference of excel file columns */
                        $arrayDiff = array_diff($supplierValues, $cleanedArray);

                        /** Checking the difference if arrayDiff empty then break the loop and go to next step */
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

        /** Here we return the error into form of json */
        if ($validationCheck == false) {
            $missingColumns = implode(', ', $arrayDiff);
            return response()->json(['error' => "We're sorry, but it seems the file you've uploaded does not meet the required format. Following ".$missingColumns." columns are missing in uploaded file"], 200);
        }

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
            /** Get the authenticated user */
            $user = Auth::user();

            try {
                UploadedFiles::create([
                    'supplier_id' => $request->supplierselect,
                    'cron' => UploadedFiles::UPLOAD,
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

    public function allSupplier() {
        $pageTitle = 'Supplier Data';
        return view('admin.supplier', compact('pageTitle'));
    }

    public function ShowAllSupplier(Request $request) {
        if ($request->ajax()) {
            $response = CategorySupplier::supplierShowDataTable($request->all());
            return response()->json($response);
        }
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
            7 => 'weekly_office_depot_sample_file.xlsx',
        ];

        $destinationPath = public_path('/excel_sheets');

        /** Set the response headers */
        $headers = [
            'Content-Type' => 'application/xlsx',
            'Content-Disposition' => 'attachment; filename="'.$filename[$id].'"',
        ];
        
        return response()->download($destinationPath.'/'.$filename[$id], $filename[$id], $headers);
    }

    public function getColumns(Request $request) {
        $columns = ManageColumns::where('supplier_id',$request->dataIdValue)->get();
        return response()->json($columns);
    }

    public function saveColumns(Request $request) {
        foreach ($request->all() as $key => $value) {
            $id = $value['fieldId'];
            $columnValue = $value['fieldValue'];
            $column = ManageColumns::find($id);
            if ($column) {
                $column->raw_label = $columnValue;
                $column->save();
            } 
        }
        return response()->json(['status' => 'success', 'message' => 'Column value updated successfully'], 200);

    }

    public function getExportWithAjax(Request $request) {
        if ($request->ajax()) {
            $formatuserdata = UploadedFiles::getFilterdExcelData($request->all());
            return response()->json($formatuserdata);
        }
    }
 
    public function editSupplierName(Request $request) {
        $validator = Validator::make($request->all(),
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

    public function addSupplierName(Request $request) {
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

    public function supplierAdd(Request $request) {
        $validator = Validator::make($request->all(),
            [
                'show' => 'required',
                'category' => 'required',
                'supplier_name' => 'required',
            ],
        );

        if ( $validator->fails()) {  
            return response()->json(['error' => $validator->errors()], 200);
        }

        CategorySupplier::create([
            'created_by' => Auth::id(),
            'show' => $request->input('show'),
            'category' => $request->input('category'),
            'supplier_name' => $request->input('supplier_name'),
        ]);
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

    public function getSupplierDetailWithAjax(Request $request) {
        if ($request->ajax()) {
            $supplierData = SupplierDetail::getSupplierDetailFilterdData($request->all());
            return response()->json($supplierData);
        }
    }

    public function editSupplierShowHide(Request $request) {
        $validator = Validator::make($request->all(),
            [
                'id' => 'required',
                'show' => 'required',
            ],
        );

        if ( $validator->fails()) {  
            return response()->json(['error' => $validator->errors()], 200);
        }

        CategorySupplier::where('id', $request->input('id'))
        ->update(['hide_show' => $request->input('show')]);
        return response()->json(['success' => true], 200);
    }

    public function supplierUpdate(Request $request) {
        $validator = Validator::make($request->all(),
            [
                'show' => 'required',
                'category' => 'required',
                'supplier_name' => 'required',
                'supplier_id' => 'required',
            ],
        );

        if ( $validator->fails()) {  
            return response()->json(['error' => $validator->errors()], 200);
        }

        CategorySupplier::where('id', $request->input('supplier_id'))
        ->update([
            'hide_show' => $request->input('show'),
            'category' => $request->input('category'),
            'supplier_name' => $request->input('supplier_name'),
        ]);
        
        return response()->json(['success' => true], 200);
    }

    public function supplierFileFormatImport(Request $request) {
        try {
            if (!empty($request->input('supplier_id'))) {
                $fileColumnsData = DB::table('supplier_fields')
                ->select([
                    'supplier_fields.id as manage_columns_id',
                    'supplier_fields.label as raw_label',
                    'supplier_fields.required_field_id as required_field_id',
                ])
                ->where([
                    'supplier_fields.supplier_id' => $request->input('supplier_id'),
                    'deleted' => 0,
                ])
                ->get();

                $fields = RequiredFieldName::all();
                $finalArray = [];
                foreach ($fileColumnsData as $key => $values) {
                    $mapColumns = '<select class="form-select form-select-sm excel_col" aria-label=".form-select-sm example" name="required_field_id[]">
                    <option value="0" selected>--Select--</option>';

                    foreach ($fields as $key => $value) {
                        if ($values->required_field_id == $value->id) {
                            $mapColumns .= '<option selected value="'.$value->id.'" >'.$value->fields_select_name.((in_array($value->id, [5, 6, 7, 8, 9]) ? (' *') : (''))).'</option>';
                        } else {
                            $mapColumns .= '<option value="'.$value->id.'" >'.$value->fields_select_name.((in_array($value->id, [5, 6, 7, 8, 9]) ? (' *') : (''))).'</option>';
                        }
                    }

                    $mapColumns .= '</select>';

                    $finalArray[] = [
                        'excel_field' => '<input type="text" class="form-control" name="raw_label[]" value="'.$values->raw_label.'"',
                        'map_columns' => '<input type="hidden" name="manage_columns_id[]" value="'.$values->manage_columns_id.'">'.$mapColumns
                    ];
                }

                return response()->json(['success' => true, 'final' => $finalArray], 200);
            } else {   
                if ($request->file('excel_file') != '') {
                    $inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($request->file('excel_file'));
                    if ($inputFileType === 'Xlsx') {
                        $reader = new Xlsx();
                    } elseif ($inputFileType === 'Xls') {
                        $reader = new Xls();
                    } else {
                        return response()->json(['error' => 'Unsupported file type: ' . $inputFileType], 200);
                    }
        
                    $spreadSheet = $reader->load($request->file('excel_file'), 2);
                    $maxNonEmptyCount = 0;
                    foreach ($spreadSheet->getAllSheets()[0]->toArray() as $key=>$value) {
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
                        return trim(str_replace(["\r", "\n"], '', $value));
                    }, $finalExcelKeyArray1);
    
                    $finalArray = [];
                    
                    $fields = RequiredFieldName::all();
                    $mapColumns = '<select class="form-select form-select-sm excel_col" aria-label=".form-select-sm example" name="required_field_id[]">
                    <option value="0" selected>--Select--</option>';
    
                    foreach ($fields as $key => $value) {
                        $mapColumns .= '<option value="'.$value->id.'" >'.$value->fields_select_name.((in_array($value->id, [5, 6, 7, 8, 9]) ? (' *') : (''))).'</option>';
                    }
    
                    $mapColumns .= '</select>'; 
    
                    foreach ($cleanedArray as $key => $value) {
                        $finalArray[] = [
                            'excel_field' => '<input type="text" class="form-control" name="raw_label[]" value="'.$value.'">',
                            'map_columns' => $mapColumns
                        ];
                    }
                    // dd($cleanedArray);
                    return response()->json(['success' => true, 'final' => $finalArray], 200);
                } else {
                    return response()->json(['error' => 'Please select your file'], 200);
                }
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 200);
        }
    }

    public function addSupplierFileFormatImport(Request $request) {
        $validator = Validator::make($request->all(),
            [
                'raw_label' => 'required',
                'supplier_id' => 'required',
            ],
        );

        if ( $validator->fails()) {  
            return response()->json(['error' => $validator->errors()], 200);
        }

        if ($request->input('supplier_id') == 7) {
            $fields = [
                5 => 'customer_number',
                6 => 'customer_name',
            ];
        } else {
            $fields = [
                5 => 'customer_number',
                6 => 'customer_name',
                7 => 'cost',
                8 => 'invoice_no',
                9 => 'date',
            ];
        }

        /** Get the keys of the $fields array */
        $field_keys = array_keys($fields);

        /** Collect missing keys */
        $missing_keys = [];

        foreach ($field_keys as $key) {
            if (!in_array((string)$key, $request->input('required_field_id'))) {
                $missing_keys[] = $fields[$key];
            }
        }

        if (!empty($missing_keys)) {
            return response()->json(['error' => "The following field keys are not present in the Map column: " . implode(', ', $missing_keys) . "."], 200);
        }

        foreach ($request->input('required_field_id') as $key => $value) {
            DB::table('supplier_fields')->insert([
                'required' => (($value != 0 ) ? (1) : (0)),
                'supplier_id' => $request->input('supplier_id'),
                'label' => $request->input('raw_label')[$key],
                'raw_label' => preg_replace('/^_+|_+$/', '',strtolower(preg_replace('/[^A-Za-z0-9_]/', '', str_replace(' ', '_', $request->input('raw_label')[$key])))),
                'type' => (($value == 7) ? ('decimal') : (($value == 9) ? ('date') : ('string'))),
                'required_field_id' => (($value != 0 ) ? ($value) : (null)),
            ]);
        }

        $tableName = DB::table('supplier_tables')->select('table_name')->where('supplier_id', $request->input('supplier_id'))->first();
        if (!$tableName) {
            $supplierName = DB::table('suppliers')->select('supplier_name')->where('id', $request->input('supplier_id'))->first();
            $tableName = preg_replace('/^_+|_+$/', '',strtolower(preg_replace('/[^A-Za-z0-9_]/', '', str_replace(' ', '_', $supplierName->supplier_name))));
        } else {
            $tableName = $tableName->table_name;
        }

        $columns = $request->input('raw_label');
        $requiredFieldId = $request->input('required_field_id');

        /** Check if the table already exists */
        if (Schema::hasTable($tableName)) {
            $newTableName = $tableName . '_old_' . time();
            Schema::rename($tableName, $newTableName);
        }

        /** Create the table */
        Schema::create($tableName, function (Blueprint $table) use ($columns, $requiredFieldId) {
            $table->id();
            $table->bigInteger('attachment_id')->unsigned()->index(); /** Adding the attachment_id column */
            foreach ($columns as $key => $column) {
                /** Replace spaces with underscores, remove unwanted characters, and convert to lowercase */
                $column = preg_replace('/^_+|_+$/', '',strtolower(preg_replace('/[^A-Za-z0-9_]/', '', str_replace(' ', '_', $column))));
                if (!empty($column)) {
                    if ($requiredFieldId[$key] == 7) {
                        $table->decimal($column)->nullable();
                    } elseif ($requiredFieldId[$key] == 9) {
                        $table->date($column)->nullable();
                    } else {
                        $table->string($column)->nullable();
                    }
                }
            }
            $table->timestamps(); /** This adds created_at and updated_at columns */
        });

        return response()->json(['success' => "Columns added successfully"], 200);
    }

    public function editSupplierFileFormatImport(Request $request) {
        $validator = Validator::make($request->all(),
            [
                'manage_columns_id' => 'required',
            ],
        );

        if ( $validator->fails()) {  
            return response()->json(['error' => $validator->errors()], 200);
        }

        if ($request->input('supplier_id') == 7) {
            $fields = [
                5 => 'customer_number',
                6 => 'customer_name',
            ];
        } else {
            $fields = [
                5 => 'customer_number',
                6 => 'customer_name',
                7 => 'cost',
                8 => 'invoice_no',
                9 => 'date',
            ];
        }

        /** Get the keys of the $fields array */
        $field_keys = array_keys($fields);

        /** Collect missing keys */
        $missing_keys = [];
        foreach ($request->input('raw_label') as $key => $value) {
            if (empty(trim($value))) {
                return response()->json(['error' => "Please fill all columns"], 200);    
            }
        }

        foreach ($field_keys as $key) {
            if (!in_array((string)$key, $request->input('required_field_id'))) {
                $missing_keys[] = $fields[$key];
            }
        }

        if (!empty($missing_keys)) {
            return response()->json(['error' => "The following field keys are not present in the Map column" . implode(', ', $missing_keys) . "."], 200);
        }

        foreach ($request->input('required_field_id') as $key => $value) {
            DB::table('supplier_fields')
            ->where('id', $request->input('manage_columns_id')[$key])
            ->update([
                'label' => $request->input('raw_label')[$key],
                'required_field_id' => (($value != 0 ) ? ($value) : (null)), 
                'type' => (($value == 7) ? ('decimal') : (($value == 9) ? ('date') : ('string'))),
                'raw_label' => preg_replace('/^_+|_+$/', '',strtolower(preg_replace('/[^A-Za-z0-9_]/', '', str_replace(' ', '_', $request->input('raw_label')[$key])))),
            ]);
        }

        $tableName = DB::table('supplier_tables')->select('table_name')->where('supplier_id', $request->input('supplier_id'))->first();

        if (!$tableName) {
            $supplierName = DB::table('suppliers')->select('supplier_name')->where('id', $request->input('supplier_id'))->first();
            $tableName = preg_replace('/^_+|_+$/', '',strtolower(preg_replace('/[^A-Za-z0-9_]/', '', str_replace(' ', '_', $supplierName->supplier_name))));
        } else {
            $tableName = $tableName->table_name;
        }

        $columns = $request->input('raw_label');
        $requiredFieldId = $request->input('required_field_id');

       /** Check if the table already exists */
        if (Schema::hasTable($tableName)) {
            /** Check if the table is empty */
            $rowCount = DB::table($tableName)->count();
            if ($rowCount == 0) {
                Schema::drop($tableName);
            } else {
                $newTableName = $tableName . '_old_' . time();
                Schema::rename($tableName, $newTableName);
            }
        }


        /** Create the table */
        Schema::create($tableName, function (Blueprint $table) use ($columns, $requiredFieldId) {
            $table->id();
            $table->bigInteger('attachment_id')->unsigned()->index(); /** Adding the attachment_id column */
            foreach ($columns as $key => $column) {
                /** Replace spaces with underscores, remove unwanted characters, and convert to lowercase */
                $column = preg_replace('/^_+|_+$/', '',strtolower(preg_replace('/[^A-Za-z0-9_]/', '', str_replace(' ', '_', $column))));
                if (!empty($column)) {
                    if ($requiredFieldId[$key] == 7) {
                        $table->decimal($column)->nullable();
                    } elseif ($requiredFieldId[$key] == 9) {
                        $table->date($column)->nullable();
                    } else {
                        $table->string($column)->nullable();
                    }
                }
            }
            $table->timestamps(); /** This adds created_at and updated_at columns */
        });
        
        return response()->json(['success' => "Column updated successfully"], 200);
    }

    public function removeSupplierFileFormatImport(Request $request) {
        DB::table('supplier_fields')
        ->where(
            'supplier_id',
            $request->input('id')
        )
        ->update(['deleted' => 1]);

        return response()->json(['success' => "Columns deleted successfully"], 200);
    }

    public function editSupplierPermissions($id){
        /** Find the user by ID */
        $supplier = CategorySupplier::with('showPermissions')->find($id);

        /** Get all permissions */
        $showPermissions = ShowPermissions::all();

        /** Return user and permissions data as JSON response */
        return response()->json([
            'supplier' => $supplier,
            'show_permissions' => $showPermissions
        ]);
    }

    public function updateSupplierPermissions(Request $request){
        $validator = Validator::make($request->all(), [
            'supplier_id' => 'required',
            'show_permissions' => 'required',
        ]);
        
        if ($validator->fails()) {  
            return response()->json(['error' => $validator->errors()], 200);
        } else {
            try {
                $supplier = CategorySupplier::find($request->supplier_id);

                /** Sync supplier permissions */
                $supplier->showPermissions()->sync($request->input('show_permissions'));
                return response()->json(['success' => true], 200);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 200);
            }
        }
    }
}