<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\{
    CheckActive,
    CatalogItem,
    Manufacturer,
    CatalogPrices,
    CatalogPriceTypes,
    CatalogAttachments,
    CatalogPriceHistory,
    CatalogSupplierFields,
    ProductDetailsCategory,
    ProductDetailsRawValue,
    ProductDetailsSubCategory,
    ProductDetailsCommonValue,
    ProductDetailsCommonAttribute,
};
// use PhpOffice\PhpSpreadsheet\Settings;
// use Cache\Adapter\Apcu\ApcuCachePool;
// use Cache\Bridge\SimpleCache\SimpleCacheBridge;

// use PhpOffice\PhpSpreadsheet\Settings;
// use Symfony\Component\Cache\Adapter\FilesystemAdapter;
// use Cache\Bridge\SimpleCache\SimpleCacheBridge;

use Illuminate\Support\Facades\Cache;

use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\{DB, Log};
use PhpOffice\PhpSpreadsheet\Reader\{Xls, Xlsx};

class CatalogUploadProcess extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:catalog-upload-process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        /** Increasing the memory limit becouse memory limit issue */
        // ini_set('memory_limit', '4G');

        /** This is the folder path where we save the file */
        $destinationPath = public_path('/excel_sheets');

        /** Select those file name where cron is 11 */
        $fileValue = CatalogAttachments::select('id', 'supplier_id', 'file_name', 'catalog_price_type_id', 'created_by')
            ->where('cron', '=', 11)
            ->whereNull('deleted_by')
            ->first();

        // if ($fileValue->supplier_id == 3) {
        //     $industryId = 1;
        // } elseif ($fileValue->supplier_id == 2) {
        //     $industryId = 2;
        // } else {
        // }

        // $suppliers = CatalogSupplierFields::getRequiredColumns();

        if ($fileValue !== null) {
            /** Update cron two means start processing data into excel */
            // CatalogAttachments::where('id', $fileValue->id)
            // ->update(['cron' => 2]);

            /** Add column name here those row you want to skip */
            // $skipRowArray = ["Shipto Location Total", "Shipto & Location Total", "TOTAL FOR ALL LOCATIONS", "Total"];

            $columnValues = CatalogSupplierFields::select(
                'catalog_supplier_fields.label as label',
                'catalog_required_fields.field_name as required_field_column'
            )
            ->leftJoin('catalog_required_fields', 'catalog_supplier_fields.catalog_required_field_id', '=', 'catalog_required_fields.id')
            ->where(['supplier_id' => $fileValue->supplier_id, 'deleted' => 0])
            ->get();

            // Convert the collection into an associative array
            $headerMapping = $columnValues->pluck('required_field_column', 'label')->toArray();

            try {
                /** Increasing the memory limit becouse memory limit issue */
                ini_set('memory_limit', '4G');

                /** For memory optimization unset the spreadSheet and reader */
                unset($spreadSheet, $reader);

                $inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($destinationPath . '/' . $fileValue->file_name);

                if ($inputFileType === 'Xlsx') {
                    $reader = new Xlsx();
                } elseif ($inputFileType === 'Xls') {
                    $reader = new Xls();
                } else {
                    // throw new Exception('Unsupported file type: ' . $inputFileType);
                }

                /** Load only the data (without formatting) to save memory */
                $reader->setReadDataOnly(true);

                /** Loading excel file using path and name of file from table "uploaded_file" */
                // $spreadSheet = $reader->load($destinationPath . '/' . $fileValue->file_name, 2);
                // $worksheet = $spreadSheet->getActiveSheet();
                // $sheetCount = $spreadSheet->getSheetCount();
                /** Getting sheet count for run loop on index */

                 // Use Laravel's cache for optimization (optional)
                // $cache = Cache::store('file')->remember('catalog_cache', 3600, function() use ($worksheet) {

                // Initialize data array
                $data = [];
                $header = []; // Initialize header array to store the first row

                foreach ($reader->load($destinationPath . '/' . $fileValue->file_name, 2)->getActiveSheet()->getRowIterator() as $rowIndex => $row) {
                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(true); // Only iterate through non-empty cells
                    
                    $rowData = [];
                    foreach ($cellIterator as $cell) {
                        $rowData[] = $cell->getFormattedValue(); // Collect the cell values
                    }

                    // If it's the first row, treat it as the header
                    if ($rowIndex === 1) {
                        $header = $rowData;  // This will be your dynamic array2
                        continue; // Skip processing for the header row
                    }

                    // Match each row data value to its corresponding header
                    $matchedRow = [];
                    foreach ($rowData as $index => $value) {
                        if (isset($header[$index])) {
                            $headerValue = $header[$index]; // Original header value
                            // Replace the header with the new mapping if exists
                            if (isset($headerMapping[$headerValue])) {
                                $matchedRow[$headerMapping[$headerValue]] = $value; // Map to new header name
                            } else {
                                $matchedRow[$headerValue] = $value; // No mapping, keep original
                            }
                        }
                    }

                    // Add the matched row to data
                    $data[] = $matchedRow;
                }

                // Output the processed data
                dd($data);

                /** Run the for loop for excel sheets */
                // for ($i = 0; $i < $sheetCount; $i++) {
                //     $count = $maxNonEmptyCount = 0;

                //     // CatalogAttachments::where('id', $fileValue->id)
                //     // ->update(['cron' => 4]);

                //     // $workSheetArray = $spreadSheet->getSheet($i)->toArray();
                //     /** Getting worksheet using index */

                //     foreach ($spreadSheet->getSheet($i)->toArray() as $key => $value) {
                //         $finalExcelKeyArray1 = array_values(
                //             array_filter(
                //                 $value,
                //                 function ($item) {
                //                     return !empty($item);
                //                 },
                //                 ARRAY_FILTER_USE_BOTH
                //             )
                //         );

                //         /** Clean up the values */
                //         $cleanedArray = array_map(function ($values) {
                //             /** Remove line breaks and trim whitespace */
                //             return trim(str_replace(["\r", "\n"], '', $values));
                //         }, $finalExcelKeyArray1);

                //         if (isset($suppliers[$fileValue->supplier_id])) {
                //             $supplierValues = $suppliers[$fileValue->supplier_id];
                //             $arrayDiff = array_diff($supplierValues, $cleanedArray);

                //             if (empty($arrayDiff)) {
                //                 $maxNonEmptyValue = $value;
                //                 $startIndexValueArray = $key;
                //                 break;
                //             }
                //         }
                //     }

                //     if (!isset($maxNonEmptyValue)) {
                //         continue;
                //     }

                //     foreach ($spreadSheet->getSheet($i)->toArray() as $key => $value) {
                //         $finalExcelKeyArray1 = array_values(
                //             array_filter(
                //                 $value,
                //                 function ($item) {
                //                     return !empty($item);
                //                 },
                //                 ARRAY_FILTER_USE_BOTH
                //             )
                //         );

                //         /** Clean up the values */
                //         $cleanedArray = array_map(function ($values) {
                //             /** Remove line breaks and trim whitespace */
                //             return trim(str_replace(["\r", "\n"], '', $values));
                //         }, $finalExcelKeyArray1);

                //         if (isset($suppliers[$fileValue->supplier_id])) {
                //             $supplierValues = $suppliers[$fileValue->supplier_id];

                //             /** Calculate the difference between the $supplierValues array and the $cleanedArray */
                //             /** This returns an array containing all elements from $supplierValues that are not present in $cleanedArray */
                //             $arrayDiff = array_diff($supplierValues, $cleanedArray);

                //             if (empty($arrayDiff)) {
                //                 $maxNonEmptyValue = $value;
                //                 $startIndexValueArray = $key;
                //                 break;
                //             }
                //         }
                //     }

                //     if (!isset($maxNonEmptyValue)) {
                //         continue;
                //     }

                //     /** Clean up the values */
                //     $maxNonEmptyValue = array_map(function ($value) {
                //         /** Remove line breaks and trim whitespace */
                //         return str_replace(["\r", "\n"], '', $value);
                //     }, $maxNonEmptyValue);

                //     /** Unset the "$maxNonEmptyCount" for memory save */
                //     unset($maxNonEmptyCount);

                //     /** Specify the starting index for get the excel column value */
                //     $startIndex = $startIndexValueArray;

                //     /** Unset the "$startIndexValueArray" for memory save */
                //     unset($startIndexValueArray);

                //     /** Here we will getting the sku key */
                //     if (!empty($columnArray[$fileValue->supplier_id]['sku'])) {
                //         $sku = array_search($columnArray[$fileValue->supplier_id]['sku'], $maxNonEmptyValue);
                //     }

                //     /** Here we will getting the unit_of_measure key */
                //     if (!empty($columnArray[$fileValue->supplier_id]['unit_of_measure'])) {
                //         $unit_of_measure = array_search($columnArray[$fileValue->supplier_id]['unit_of_measure'], $maxNonEmptyValue);
                //     }

                //     /** Here we will getting the manufacterer_number key */
                //     if (!empty($columnArray[$fileValue->supplier_id]['manufacterer_number'])) {
                //         $manufacterer_number = array_search($columnArray[$fileValue->supplier_id]['manufacterer_number'], $maxNonEmptyValue);
                //     }

                //     /** Here we will getting the supplier_shorthand_name key */
                //     if (!empty($columnArray[$fileValue->supplier_id]['supplier_shorthand_name'])) {
                //         $supplier_shorthand_name = array_search($columnArray[$fileValue->supplier_id]['supplier_shorthand_name'], $maxNonEmptyValue);
                //     }

                //     /** Here we will getting the value key */
                //     if (!empty($columnArray[$fileValue->supplier_id]['value'])) {
                //         $value = array_search($columnArray[$fileValue->supplier_id]['value'], $maxNonEmptyValue);
                //     }

                //     /** Here we will getting the category_name key */
                //     if (!empty($columnArray[$fileValue->supplier_id]['category_name'])) {
                //         $category_name = array_search($columnArray[$fileValue->supplier_id]['category_name'], $maxNonEmptyValue);
                //     }

                //     /** Here we will getting the sub_category_name key */
                //     if (!empty($columnArray[$fileValue->supplier_id]['sub_category_name'])) {
                //         $sub_category_name = array_search($columnArray[$fileValue->supplier_id]['sub_category_name'], $maxNonEmptyValue);
                //     }

                //     /** Here we will getting the manufacturer_name key */
                //     if (!empty($columnArray[$fileValue->supplier_id]['manufacturer_name'])) {
                //         $manufacturer_name = array_search($columnArray[$fileValue->supplier_id]['manufacturer_name'], $maxNonEmptyValue);
                //     }

                //     /** Here we will getting the unit_of_measure key */
                //     if (!empty($columnArray[$fileValue->supplier_id]['unit_of_measure'])) {
                //         $unit_of_measure = array_search($columnArray[$fileValue->supplier_id]['unit_of_measure'], $maxNonEmptyValue);
                //     }

                //     // if (isset($spreadSheet->getSheet($i)->toArray()) && !empty($spreadSheet->getSheet($i)->toArray())) {
                //         /** For insert data into the database */
                //         foreach ($spreadSheet->getSheet($i)->toArray() as $key => $row) {
                //             if ($key > $startIndex && !empty($row[$category_name])) {
                //                 // dd($row[$category_name]);
                //                 /** Check if the category exists, if not, create it */
                //                 $category = ProductDetailsCategory::firstOrCreate(
                //                     ['category_name' => $row[$category_name]],
                //                     [
                //                         'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                //                         'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
                //                     ]
                //                 );

                //                 /** Now $category contains the existing or newly created record */
                //                 $category_id = $category->id;
                //                 /** Get the last inserted or existing category id */

                //                 /** Check if the subcategory exists, if not, create it */
                //                 $sub_category = ProductDetailsSubCategory::firstOrCreate(
                //                     [
                //                         'category_id' => $category_id,
                //                         'sub_category_name' => $row[$sub_category_name]
                //                     ],
                //                     [
                //                         'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                //                         'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
                //                     ]
                //                 );

                //                 /** Now $sub_category contains the existing or newly created record */
                //                 $sub_category_id = $sub_category->id;
                //                 /** Get the last inserted or existing subcategory id */

                //                 /** Check if the manufacturer exists, if not, create it */
                //                 $manufacturer = Manufacturer::firstOrCreate(
                //                     ['manufacturer_name' => $row[$manufacturer_name]],
                //                     [
                //                         'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                //                         'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
                //                     ]
                //                 );

                //                 /** Now $manufacturer contains the existing or newly created record */
                //                 $manufacturer_id = $manufacturer->id;
                //                 /** Get the last inserted or existing manufacturer id */

                //                 if ($fileValue->catalog_price_type_id == 2) {
                //                     /** Check if the common attribute exists, if not, create it */
                //                     $common_attribute = ProductDetailsCommonAttribute::firstOrCreate(
                //                         [
                //                             'sub_category_id' => $sub_category_id,
                //                             'attribute_name' => $row[$manufacturer_name]
                //                         ],
                //                         [
                //                             'type' => '',
                //                             'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                //                             'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
                //                         ]
                //                     );

                //                     /** Now $common_attribute contains the existing or newly created record */
                //                     $common_attribute_id = $common_attribute->id;
                //                     /** Get the last inserted or existing common attribute id */
                //                 }

                //                 /** Check if the catalog item exists by sku, if not, create it */
                //                 $catalog_item = CatalogItem::firstOrCreate(
                //                     ['sku' => $row[$sku]],
                //                     /** Unique condition based on sku */
                //                     [
                //                         'unspsc' => '',
                //                         'industry_id' => $industryId,
                //                         'category_id' => $category_id,
                //                         'sub_category_id' => $sub_category_id,
                //                         'manufacturer_id' => $manufacturer_id,
                //                         'supplier_id' => $fileValue->supplier_id,
                //                         'unit_of_measure' => $row[$unit_of_measure],
                //                         'catalog_item_url' => '',
                //                         'catalog_item_name' => '',
                //                         'quantity_per_unit' => '',
                //                         'manufacturer_number' => $row[$manufacterer_number],
                //                         'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                //                         'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                //                         'supplier_shorthand_name' => $row[$supplier_shorthand_name],
                //                         'catalog_price_type_id' => $fileValue->catalog_price_type_id,
                //                     ]
                //                 );

                //                 /** Now $catalog_item contains the existing or newly created record */
                //                 $catalog_item_id = $catalog_item->id;
                                
                //                 if ($fileValue->catalog_price_type_id == 1) {
                //                     CheckActive::where(['catalog_item_id' => $catalog_item_id, 'catalog_price_type_id' => $fileValue->catalog_price_type_id])
                //                     ->update([
                //                         'active' => 0,
                //                     ]);
                //                 }

                //                 $existingCheckActiveRecord = CheckActive::where('catalog_item_id', $catalog_item_id)->first();

                //                 if ($existingCheckActiveRecord) {
                //                     /** Update the existing record */
                //                     $existingCheckActiveRecord->update([
                //                         'active' => 1,
                //                         'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                //                     ]);
                //                 } else {
                //                     /** Create the record if not existing */
                //                     CheckActive::create([
                //                         'active' => 1,
                //                         'catalog_item_id' => $catalog_item_id,
                //                         'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                //                         'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                //                         'catalog_price_type_id' => $fileValue->catalog_price_type_id,
                //                     ]);
                //                 }

                //                 if ($fileValue->catalog_price_type_id == 2) {
                //                     /** Get the last inserted or existing catalog item id */
    
                //                     /** Check if the common value exists, if not, create it */
                //                     $product_details_common_value = ProductDetailsCommonValue::firstOrCreate(
                //                         [
                //                             'value' => $row[$value],
                //                             'catalog_item_id' => $catalog_item_id,
                //                             'common_attribute_id' => $common_attribute_id
                //                         ],
                //                         [
                //                             'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                //                             'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
                //                         ]
                //                     );
    
                //                     /** Now $product_details_common_value contains the existing or newly created record */
                //                     $product_details_common_value_id = $product_details_common_value->id;
                //                     /** Get the last inserted or existing record id */
    
                //                     /** Check if the raw value exists, if not, create it */
                //                     $product_details_raw_value = ProductDetailsRawValue::firstOrCreate(
                //                         ['catalog_item_id' => $catalog_item_id],
                //                         [
                //                             'raw_values' => '',
                //                             'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                //                             'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
                //                         ]
                //                     );
    
                //                     /** Now $product_details_raw_value contains the existing or newly created record */
                //                     $product_details_raw_value_id = $product_details_raw_value->id; /** Get the last inserted or existing record id */
                //                 }

                //                 $existingRecord = CatalogPrices::where('catalog_item_id', $catalog_item_id)->first();

                //                 if ($existingRecord) {
                //                     // Update the existing record
                //                     $existingRecord->update([
                //                         'value' => $row[$value],
                //                         'customer_id' => $row[10],
                //                         'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                //                         'catalog_price_type_id' => $fileValue->catalog_price_type_id,
                //                         'core_list' => (trim($row[11]) == 'Contract Pricing') ? 1 : 0,
                //                     ]);
                //                 } else {
                //                     // Insert a new record
                //                     CatalogPrices::create([
                //                         'value' => $row[$value],
                //                         'customer_id' => $row[10],
                //                         'catalog_item_id' => $catalog_item_id,
                //                         'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                //                         'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                //                         'catalog_price_type_id' => $fileValue->catalog_price_type_id,
                //                         'core_list' => (trim($row[11]) == 'Contract Pricing') ? 1 : 0,
                //                     ]);
                //                 }

                //                 CatalogPriceHistory::create([
                //                     'value' => $row[$value],
                //                     'customer_id' => $row[10],
                //                     'catalog_item_id' => $catalog_item_id,
                //                     'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                //                     'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                //                     'catalog_price_type_id' => $fileValue->catalog_price_type_id,
                //                     'core_list' => (trim($row[11]) == 'Contract Pricing') ? 1 : 0,
                //                 ]);
                //             }
                //         }

                //         /** For memory optimization we unset the workSheetArray1 and count */
                //         // unset($workSheetArray1, $count);

                //         if (isset($finalInsertArray) && !empty($finalInsertArray)) {
                //             try {
                //                 /** Updating the file upload status */
                //                 CatalogAttachments::where('id', $fileValue->id)
                //                     ->update(['cron' => 5]);

                //                 /** Inserting the data into the spacific supplier table */
                //             } catch (QueryException $e) {
                //                 /** Handling the QueryException using try catch */
                //                 Log::error('Error in YourScheduledTask: ' . $e->getMessage());
                //                 echo "Database insertion failed: " . $e->getMessage();
                //             }
                //         }

                //         /** For memory optimization we unset the excelInsertArray */
                //         unset($excelInsertArray);
                //     // }
                // }

                // try {
                //     /** Update the 'cron' field six after processing done */
                //     CatalogAttachments::where('id', $fileValue->id)
                //         ->update(['cron' => 6]);

                //     $this->info('Uploaded files processed successfully.');
                // } catch (QueryException $e) {
                //     /** Handling the QueryException using try catch */
                //     Log::error('Database updation failed: ' . $e->getMessage());
                //     echo "Database updation failed: " . $e->getMessage();
                //     die;
                // }
            } catch (\Exception $e) {
                /** Handling the Exception using try catch */
                Log::error('Exception loading spreadsheet: ' . $e->getMessage());
                echo "Error loading spreadsheet: " . $e->getMessage();
                die;
            }
        }
    }
}