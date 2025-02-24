<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{
    CategorySupplier,
    RebateSupplierFields,
    SupplierRebateRequiredFields,
    SupplierValidationAttachments
};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class SupplierValidation extends Controller
{
    public function index() {
        $categorySuppliers = CategorySupplier::where('show', 0)->where('show', '!=', 1)->get();
        $pageTitle = "Import Supplier Rebate File";
 
        return view('admin.supplier_validation_export',compact('categorySuppliers', 'pageTitle'));
    }

    public function getSupplierValidationExportWithAjax(Request $request) {
        if ($request->ajax()) {
            $formatuserdata = SupplierValidationAttachments::getSupplierValidationFilterdExcelData($request->all());
            return response()->json($formatuserdata);
        }
    }

    public function supplierValidationRebateFileFormatImport(Request $request) {
        try {
            if (!empty($request->input('supplier_id'))) {
                $fileColumnsData = RebateSupplierFields::select([
                    'label as raw_label',
                    'id as manage_columns_id',
                    'rebate_supplier_required_field_id as required_field_id',
                ])
                ->where([
                    'supplier_id' => $request->input('supplier_id'),
                    'deleted' => 0,
                ])
                ->get();

                $fields = SupplierRebateRequiredFields::all();
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
                    
                    $fields = SupplierRebateRequiredFields::all();
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

    public function addSupplierValidationRebateFileFormatImport(Request $request) {
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
                'rebate_supplier_required_field_id' => (($value != 0 ) ? ($value) : (null)),
            ];
        }

        if (!empty($insertArray)) {
            DB::table('rebate_supplier_fields')->insert($insertArray);
        }
        
        return response()->json(['success' => "Columns added successfully"], 200);
    }

    public function editSupplierValidationRebateFileFormatImport(Request $request) {
        $validator = Validator::make(
            $request->all(),
            ['manage_columns_id' => 'required'],
        );

        if ($validator->fails()) {  
            return response()->json(['error' => $validator->errors()], 200);
        }

        foreach ($request->input('required_field_id') as $key => $value) {
            DB::table('rebate_supplier_fields')
            ->where('id', $request->input('manage_columns_id')[$key])
            ->update([
                'label' => $request->input('raw_label')[$key],
                'rebate_supplier_required_field_id' => (($value != 0 ) ? ($value) : (null)), 
                'type' => (($value == 7) ? ('decimal') : (($value == 9) ? ('date') : ('string'))),
                'raw_label' => preg_replace('/^_+|_+$/', '',strtolower(preg_replace('/[^A-Za-z0-9_]/', '', str_replace(' ', '_', $request->input('raw_label')[$key])))),
            ]);
        }

        return response()->json(['success' => "Column updated successfully"], 200);
    }

    public function removeSupplierValidationRebateFileFormatImport(Request $request) {
        DB::table('rebate_supplier_fields')
        ->where(
            'supplier_id',
            $request->input('id')
        )
        ->update(['deleted' => 1]);

        return response()->json(['success' => "Columns deleted successfully"], 200);
    }
 
}
