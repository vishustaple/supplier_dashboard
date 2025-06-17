<?php

namespace App\Http\Controllers\Catalog;

use Exception;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\{DB, Auth, Validator};
use PhpOffice\PhpSpreadsheet\{Spreadsheet, Reader\Xls, Reader\Xlsx, Writer\Xlsx as Writer};
use App\Models\{CatalogAttachments, CatalogRequiredFields, CategorySupplier, CatalogSupplierFields};

class CatalogImportController extends Controller
{
    public function index() {
        $categorySuppliers = CategorySupplier::where('show', 0)->where('show', '!=', 1)->get();
        $uploadData = CatalogAttachments::query()->selectRaw("`catalog_attachments`.*, CONCAT(`users`.`first_name`, ' ', `users`.`last_name`) AS user_name")
        ->leftJoin('users', 'catalog_attachments.created_by', '=', 'users.id')
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
        
        $pageTitle = "Upload Catalog Sheets";
        $data = json_encode($formattedData);
        $catalogPriceType = DB::connection('second_db')->table('catalog_price_types')->where('id', '!=', 3)->get();

        return view('admin.catalog.catalog_import',compact('categorySuppliers','data', 'catalogPriceType', 'pageTitle'));
    }

    public function getExportWithAjax(Request $request) {
        if ($request->ajax()) {
            $formatuserdata = CatalogAttachments::getFilterdCatalogData($request->all());
            return response()->json($formatuserdata);
        }
    }

    public function import(Request $request) {
        /** Increasing memory for smoothly process data of excel file */
        // ini_set('memory_limit', '1024M');

        /** Getting suppliers ids and its required columns */
        $suppliers = CatalogSupplierFields::getRequiredColumns();
        
        /** Validate the uploaded file */
        $validator = Validator::make(
            ['supplierselect' => $request->supplierselect, 'file' => $request->file('file')],
            ['supplierselect' => 'required', 'file' => 'required'],
            ['supplierselect.required' => 'Please select a supplier. It is a required field.']
        );

        if( $validator->fails()){  
            $categorySuppliers = $categorySuppliers = CategorySupplier::where('show', 0)->get();
            return response()->json(['error' => $validator->errors(), 'categorySuppliers' => $categorySuppliers], 200);
        }
        
        // try {
            // /** Getting the file extension for process file according to the extension */
            // $inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($request->file('file'));

            // if ($inputFileType === 'Xlsx') {
            //     $reader = new Xlsx();
            // } elseif ($inputFileType === 'Xls') {
            //     $reader = new Xls();
            // } else {
            //     return response()->json(['error' => 'Unsupported file type: ' . $inputFileType], 200);
            //     throw new Exception('Unsupported file type: ' . $inputFileType);
            // }

            // /** Loading the file without attached image */
            // $spreadSheet = $reader->load($request->file('file'), 2);

            // /** Setting the variables for validation */
            // $validationCheck = $arrayDiff = false;

            // foreach ($spreadSheet->getAllSheets() as $spreadSheets) {
            //     if ($validationCheck == true) {
            //         break;
            //     }

            //     foreach ($spreadSheets->toArray() as $value) {
            //         /** Remove empty key from the array of excel sheet column name */
            //         $finalExcelKeyArray1 = array_values(array_filter($value, function ($item) {
            //             return !empty($item);
            //         }, ARRAY_FILTER_USE_BOTH));
                                
            //         /** Clean up the values */
            //         $cleanedArray = array_map(function ($values) {
            //             /** Remove line breaks and trim whitespace */
            //             return trim(str_replace(["\r", "\n"], '', $values));
            //         }, $finalExcelKeyArray1);

            //         if (isset($suppliers[$request->supplierselect])) {
            //             $supplierValues = $suppliers[$request->supplierselect];
                        
            //             /** Getting the difference of excel file columns */
            //             $arrayDiff = array_diff($supplierValues, $cleanedArray);

            //             if (count($arrayDiff) < count($supplierValues)) {
            //                 $arrayDiff1 = $arrayDiff;
            //             }

            //             /** Checking the difference if arrayDiff empty then break the loop and go to next step */
            //             if (empty($arrayDiff)) {
            //                 $validationCheck = true;
            //                 break;
            //             }
            //         }
            //     }
            // }
        // } catch (\Exception $e) {
        //     return redirect()->back()->with('error', $e->getMessage());
        // }
        
        // dd($supplierValues, $cleanedArray, $arrayDiff, $arrayDiff1);
        // /** Here we return the error into form of json */
        // if ($validationCheck == false) {
        //     if (isset($arrayDiff1) && !empty($arrayDiff1)) {
        //         $missingColumns = implode(', ', $arrayDiff1);
        //     } else {
        //         $missingColumns = implode(', ', $arrayDiff);
        //     }
            
        //     return response()->json(['error' => "We're sorry, but it seems the file you've uploaded does not meet the required format. Following ".$missingColumns." columns are missing in uploaded file"], 200);
        // }

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
                CatalogAttachments::create([
                    'date' => $request->date,
                    'cron' => 11,
                    'file_name' => $fileName,
                    'created_by' => $user->id,
                    'supplier_id' => $request->supplierselect,
                    'catalog_price_type_id' => $request->catalog_price_type_id,
                ]);

                /** Move the file with the new name */
                $file->move($destinationPath, $fileName);

                return response()->json(['success' => 'Excel file imported successfully!'], 200);
            } catch (QueryException $e) {   
                return response()->json(['error' => $e->getMessage()], 200);
            }

            // $suppliers = CatalogSupplierFields::getColumns();
            // $supplierValues = $suppliers[$request->supplierselect];
            // $arrayDiff = array_values(array_diff($supplierValues, $cleanedArray));
            // $column = (count($arrayDiff) > 1) ? ('columns') : ('column');
            // $missingColumns = implode(', ', $arrayDiff);
            
            // if (!empty($arrayDiff)) {
            //    return response()->json(['success' => 'Excel file imported successfully!. Missing '.$column.' '.$missingColumns.''], 200);
            // } else {
            //    return response()->json(['success' => 'Excel file imported successfully!'], 200);
            // }
        } else {
            return response()->json(['error' => 'Please select supplier.'], 200);
        }
    }

    public function supplierCatalogFileFormatImport(Request $request) {
        try {
            if (!empty($request->input('supplier_id'))) {
                $fileColumnsData = CatalogSupplierFields::select([
                    'label as raw_label',
                    'id as manage_columns_id',
                    'catalog_required_field_id as required_field_id',
                ])
                ->where([
                    'supplier_id' => $request->input('supplier_id'),
                    'deleted' => 0,
                ])
                ->get();

                $fields = CatalogRequiredFields::all();
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
                    
                    $fields = CatalogRequiredFields::all();
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

    public function addSupplierCatalogFileFormatImport(Request $request) {
        $validator = Validator::make($request->all(),
            [
                'raw_label' => 'required',
                'supplier_id' => 'required',
            ],
        );

        if ( $validator->fails()) {  
            return response()->json(['error' => $validator->errors()], 200);
        }

        $insertArray =[];
        foreach ($request->input('required_field_id') as $key => $value) {
            $insertArray[] = [
                'required' => (($value != 0 ) ? (1) : (0)),
                'supplier_id' => $request->input('supplier_id'),
                'label' => $request->input('raw_label')[$key],
                'raw_label' => preg_replace('/^_+|_+$/', '',strtolower(preg_replace('/[^A-Za-z0-9_]/', '', str_replace(' ', '_', $request->input('raw_label')[$key])))),
                'type' => (($value == 7) ? ('decimal') : (($value == 9) ? ('date') : ('string'))),
                'catalog_required_field_id' => (($value != 0 ) ? ($value) : (null)),
            ];
        }

        if (!empty($insertArray)) {
            DB::connection('second_db')->table('catalog_supplier_fields')->insert($insertArray);
        }
        
        return response()->json(['success' => "Columns added successfully"], 200);
    }

    public function editSupplierCatalogFileFormatImport(Request $request) {
        $validator = Validator::make(
            $request->all(),
            ['manage_columns_id' => 'required'],
        );

        if ($validator->fails()) {  
            return response()->json(['error' => $validator->errors()], 200);
        }

        foreach ($request->input('required_field_id') as $key => $value) {
            DB::connection('second_db')
            ->table('catalog_supplier_fields')
            ->where('id', $request->input('manage_columns_id')[$key])
            ->update([
                'label' => $request->input('raw_label')[$key],
                'catalog_required_field_id' => (($value != 0 ) ? ($value) : (null)), 
                'type' => (($value == 7) ? ('decimal') : (($value == 9) ? ('date') : ('string'))),
                'raw_label' => preg_replace('/^_+|_+$/', '',strtolower(preg_replace('/[^A-Za-z0-9_]/', '', str_replace(' ', '_', $request->input('raw_label')[$key])))),
            ]);
        }

        return response()->json(['success' => "Column updated successfully"], 200);
    }

    public function removeSupplierCatalogFileFormatImport(Request $request) {
        DB::connection('second_db')
        ->table('catalog_supplier_fields')
        ->where(
            'supplier_id',
            $request->input('id')
        )
        ->update(['deleted' => 1]);

        return response()->json(['success' => "Columns deleted successfully"], 200);
    }

    public function downloadSampleFile(Request $request, $id=null) {
        if ($id != null) {
            $id = $request->id;
        }

        $supplierColumns = DB::table('catalog_supplier_fields')
        ->where(['supplier_id' => $id, 'deleted' => 0]);
        
        $file = 'Sample';

        /** Create a new Spreadsheet */
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        /** Extract only the 'label' values */
        $labels = $supplierColumns->pluck('label')->toArray();

        /** Set header for the 'label' column */
        $sheet->setCellValue('A1', 'Label');

        /** Insert labels into a single row (starting from column A) */
        foreach ($labels as $index => $label) {
            /** The first label goes in column A, second in column B, and so on */
            $sheet->setCellValueByColumnAndRow($index + 1, 1, $label); /** (column, row) */
        }

        /** Set headers to prompt for download */
        $fileName = $file.".xlsx";
        
        /** Stream the file for download */
        $writer = new Writer($spreadsheet);
        $filePath = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($filePath);

        return response()->download($filePath, $fileName)->deleteFileAfterSend(true);
    }

    public function ShowAllSupplierCatalog(Request $request) {
        if ($request->ajax()) {
            $response = CategorySupplier::supplierCatalogShowDataTable($request->all());
            return response()->json($response);
        }
    }

    public function allSupplierCatalog() {
        $pageTitle = 'Supplier Catalog Data';
        return view('admin.catalog.catalog_file_format_add', compact('pageTitle'));
    }
}
