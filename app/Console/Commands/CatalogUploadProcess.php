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
        /** This is the folder path where we save the file */
        $destinationPath = public_path('/excel_sheets');

        /** Select those file name where cron is 11 */
        $fileValue = CatalogAttachments::select('id', 'supplier_id', 'file_name', 'catalog_price_type_id', 'created_by')
            ->where('cron', '=', 11)
            ->whereNull('deleted_by')
            ->first();

        if ($fileValue->supplier_id == 3) {
            $industryId = 1;
        } elseif ($fileValue->supplier_id == 2) {
            $industryId = 2;
        } else {
        }

        $suppliers = CatalogSupplierFields::getRequiredColumns();

        if ($fileValue !== null) {
            /** Update cron two means start processing data into excel */
            // CatalogAttachments::where('id', $fileValue->id)
            // ->update(['cron' => 2]);

            /** Add column name here those row you want to skip */
            $skipRowArray = ["Shipto Location Total", "Shipto & Location Total", "TOTAL FOR ALL LOCATIONS", "Total"];

            $columnValues = CatalogSupplierFields::select(
                'catalog_supplier_fields.id as id',
                'catalog_supplier_fields.label as label',
                'catalog_supplier_fields.raw_label as raw_label',
                'catalog_required_fields.id as required_field_id',
                'catalog_supplier_fields.supplier_id as supplier_id',
                'catalog_required_fields.field_name as required_field_column',
            )
                ->leftJoin('catalog_required_fields', 'catalog_supplier_fields.catalog_required_field_id', '=', 'catalog_required_fields.id')
                ->where(['supplier_id' => $fileValue->supplier_id, 'deleted' => 0])
                ->get();

            $columnArray = [];
            foreach ($columnValues as $key => $value) {
                if (!empty($value->required_field_column)) {
                    $columnArray[$value->supplier_id][$value->required_field_column] = $value->label;
                }
                // $columnArray2[$fileValue->supplier_id][$value->label] = $value->raw_label;
                // $columnArray1[$value->id] = $value->label;
            }

            try {
                /** Increasing the memory limit becouse memory limit issue */
                ini_set('memory_limit', '1024M');

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

                /** Loading excel file using path and name of file from table "uploaded_file" */
                $spreadSheet = $reader->load($destinationPath . '/' . $fileValue->file_name, 2);
                $sheetCount = $spreadSheet->getSheetCount();
                /** Getting sheet count for run loop on index */

                /** Run the for loop for excel sheets */
                for ($i = 0; $i < $sheetCount; $i++) {
                    $count = $maxNonEmptyCount = 0;

                    // CatalogAttachments::where('id', $fileValue->id)
                    // ->update(['cron' => 4]);

                    $workSheetArray = $spreadSheet->getSheet($i)->toArray();
                    /** Getting worksheet using index */

                    foreach ($workSheetArray as $key => $value) {
                        $finalExcelKeyArray1 = array_values(
                            array_filter(
                                $value,
                                function ($item) {
                                    return !empty($item);
                                },
                                ARRAY_FILTER_USE_BOTH
                            )
                        );

                        /** Clean up the values */
                        $cleanedArray = array_map(function ($values) {
                            /** Remove line breaks and trim whitespace */
                            return trim(str_replace(["\r", "\n"], '', $values));
                        }, $finalExcelKeyArray1);

                        if (isset($suppliers[$fileValue->supplier_id])) {
                            $supplierValues = $suppliers[$fileValue->supplier_id];
                            $arrayDiff = array_diff($supplierValues, $cleanedArray);

                            if (empty($arrayDiff)) {
                                $maxNonEmptyValue = $value;
                                $startIndexValueArray = $key;
                                break;
                            }
                        }
                    }

                    if (!isset($maxNonEmptyValue)) {
                        continue;
                    }

                    foreach ($workSheetArray as $key => $value) {
                        $finalExcelKeyArray1 = array_values(
                            array_filter(
                                $value,
                                function ($item) {
                                    return !empty($item);
                                },
                                ARRAY_FILTER_USE_BOTH
                            )
                        );

                        /** Clean up the values */
                        $cleanedArray = array_map(function ($values) {
                            /** Remove line breaks and trim whitespace */
                            return trim(str_replace(["\r", "\n"], '', $values));
                        }, $finalExcelKeyArray1);

                        if (isset($suppliers[$fileValue->supplier_id])) {
                            $supplierValues = $suppliers[$fileValue->supplier_id];

                            /** Calculate the difference between the $supplierValues array and the $cleanedArray */
                            /** This returns an array containing all elements from $supplierValues that are not present in $cleanedArray */
                            $arrayDiff = array_diff($supplierValues, $cleanedArray);

                            if (empty($arrayDiff)) {
                                $maxNonEmptyValue = $value;
                                $startIndexValueArray = $key;
                                break;
                            }
                        }
                    }

                    if (!isset($maxNonEmptyValue)) {
                        continue;
                    }

                    /** Clean up the values */
                    $maxNonEmptyValue = array_map(function ($value) {
                        /** Remove line breaks and trim whitespace */
                        return str_replace(["\r", "\n"], '', $value);
                    }, $maxNonEmptyValue);

                    /** Unset the "$maxNonEmptyCount" for memory save */
                    unset($maxNonEmptyCount);

                    $startIndex = $startIndexValueArray;
                    /** Specify the starting index for get the excel column value */

                    /** Unset the "$startIndexValueArray" for memory save */
                    unset($startIndexValueArray);

                    /** Here we will getting the sku key */
                    if (!empty($columnArray[$fileValue->supplier_id]['sku'])) {
                        $sku = array_search($columnArray[$fileValue->supplier_id]['sku'], $maxNonEmptyValue);
                    }

                    /** Here we will getting the unit_of_measure key */
                    if (!empty($columnArray[$fileValue->supplier_id]['unit_of_measure'])) {
                        $unit_of_measure = array_search($columnArray[$fileValue->supplier_id]['unit_of_measure'], $maxNonEmptyValue);
                    }

                    /** Here we will getting the manufacterer_number key */
                    if (!empty($columnArray[$fileValue->supplier_id]['manufacterer_number'])) {
                        $manufacterer_number = array_search($columnArray[$fileValue->supplier_id]['manufacterer_number'], $maxNonEmptyValue);
                    }

                    /** Here we will getting the supplier_shorthand_name key */
                    if (!empty($columnArray[$fileValue->supplier_id]['supplier_shorthand_name'])) {
                        $supplier_shorthand_name = array_search($columnArray[$fileValue->supplier_id]['supplier_shorthand_name'], $maxNonEmptyValue);
                    }

                    /** Here we will getting the value key */
                    if (!empty($columnArray[$fileValue->supplier_id]['value'])) {
                        $value = array_search($columnArray[$fileValue->supplier_id]['value'], $maxNonEmptyValue);
                    }

                    /** Here we will getting the category_name key */
                    if (!empty($columnArray[$fileValue->supplier_id]['category_name'])) {
                        $category_name = array_search($columnArray[$fileValue->supplier_id]['category_name'], $maxNonEmptyValue);
                    }

                    /** Here we will getting the sub_category_name key */
                    if (!empty($columnArray[$fileValue->supplier_id]['sub_category_name'])) {
                        $sub_category_name = array_search($columnArray[$fileValue->supplier_id]['sub_category_name'], $maxNonEmptyValue);
                    }

                    /** Here we will getting the manufacturer_name key */
                    if (!empty($columnArray[$fileValue->supplier_id]['manufacturer_name'])) {
                        $manufacturer_name = array_search($columnArray[$fileValue->supplier_id]['manufacturer_name'], $maxNonEmptyValue);
                    }

                    /** Here we will getting the unit_of_measure key */
                    if (!empty($columnArray[$fileValue->supplier_id]['unit_of_measure'])) {
                        $unit_of_measure = array_search($columnArray[$fileValue->supplier_id]['unit_of_measure'], $maxNonEmptyValue);
                    }

                    if (isset($workSheetArray) && !empty($workSheetArray)) {
                        /** For insert data into the database */
                        foreach ($workSheetArray as $key => $row) {
                            if ($key > $startIndex && !empty($row[$category_name])) {
                                // dd($row[$category_name]);
                                /** Check if the category exists, if not, create it */
                                $category = ProductDetailsCategory::firstOrCreate(
                                    ['category_name' => $row[$category_name]],
                                    [
                                        'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                        'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
                                    ]
                                );

                                /** Now $category contains the existing or newly created record */
                                $category_id = $category->id;
                                /** Get the last inserted or existing category id */

                                /** Check if the subcategory exists, if not, create it */
                                $sub_category = ProductDetailsSubCategory::firstOrCreate(
                                    [
                                        'category_id' => $category_id,
                                        'sub_category_name' => $row[$sub_category_name]
                                    ],
                                    [
                                        'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                        'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
                                    ]
                                );

                                /** Now $sub_category contains the existing or newly created record */
                                $sub_category_id = $sub_category->id;
                                /** Get the last inserted or existing subcategory id */

                                /** Check if the manufacturer exists, if not, create it */
                                $manufacturer = Manufacturer::firstOrCreate(
                                    ['manufacturer_name' => $row[$manufacturer_name]],
                                    [
                                        'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                        'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
                                    ]
                                );

                                /** Now $manufacturer contains the existing or newly created record */
                                $manufacturer_id = $manufacturer->id;
                                /** Get the last inserted or existing manufacturer id */

                                // /** Check if the common attribute exists, if not, create it */
                                // $common_attribute = ProductDetailsCommonAttribute::firstOrCreate(
                                //     [
                                //         'sub_category_id' => $sub_category_id,
                                //         'attribute_name' => $row[$manufacturer_name]
                                //     ],
                                //     [
                                //         'type' => '',
                                //         'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                //         'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
                                //     ]
                                // );

                                // /** Now $common_attribute contains the existing or newly created record */
                                // $common_attribute_id = $common_attribute->id;
                                /** Get the last inserted or existing common attribute id */

                                /** Check if the catalog item exists by sku, if not, create it */
                                $catalog_item = CatalogItem::firstOrCreate(
                                    ['sku' => $row[$sku]],
                                    /** Unique condition based on sku */
                                    [
                                        'active' => 1,
                                        'unspsc' => '',
                                        'industry_id' => $industryId,
                                        'category_id' => $category_id,
                                        'sub_category_id' => $sub_category_id,
                                        'manufacturer_id' => $manufacturer_id,
                                        'supplier_id' => $fileValue->supplier_id,
                                        'unit_of_measure' => $row[$unit_of_measure],
                                        'catalog_item_url' => '',
                                        'catalog_item_name' => '',
                                        'quantity_per_unit' => '',
                                        'manufacturer_number' => $row[$manufacterer_number],
                                        'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                        'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                        'supplier_shorthand_name' => $row[$supplier_shorthand_name],
                                        'catalog_price_type_id' => $fileValue->catalog_price_type_id,
                                    ]
                                );

                                /** Now $catalog_item contains the existing or newly created record */
                                $catalog_item_id = $catalog_item->id;

                                $existingRecord = CheckActive::where('catalog_item_id', $catalog_item_id)->first();

                                if ($existingRecord) {
                                    // Update the existing record
                                    $existingRecord->update([
                                        'value' => $row[$value],
                                        'customer_id' => $row[10],
                                        'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                        'catalog_price_type_id' => $fileValue->catalog_price_type_id,
                                        'core_list' => (trim($row[11]) == 'Contract Pricing') ? 1 : 0,
                                    ]);
                                } else {
                                    CheckActive::create([
                                        'active' => 1,
                                        'catalog_item_id' => $catalog_item_id,
                                        'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                        'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                        'catalog_price_type_id' => $fileValue->catalog_price_type_id,
                                    ]);
                                }

                                /** Get the last inserted or existing catalog item id */

                                // /** Check if the common value exists, if not, create it */
                                // $product_details_common_value = ProductDetailsCommonValue::firstOrCreate(
                                //     [
                                //         'value' => $row[$value],
                                //         'catalog_item_id' => $catalog_item_id,
                                //         'common_attribute_id' => $common_attribute_id
                                //     ],
                                //     [
                                //         'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                //         'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
                                //     ]
                                // );

                                // /** Now $product_details_common_value contains the existing or newly created record */
                                // $product_details_common_value_id = $product_details_common_value->id;
                                /** Get the last inserted or existing record id */

                                // /** Check if the raw value exists, if not, create it */
                                // $product_details_raw_value = ProductDetailsRawValue::firstOrCreate(
                                //     ['catalog_item_id' => $catalog_item_id],
                                //     [
                                //         'raw_values' => '',
                                //         'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                //         'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
                                //     ]
                                // );

                                // /** Now $product_details_raw_value contains the existing or newly created record */
                                // $product_details_raw_value_id = $product_details_raw_value->id; /** Get the last inserted or existing record id */

                                $existingRecord = CatalogPrices::where('catalog_item_id', $catalog_item_id)->first();

                                if ($existingRecord) {
                                    // Update the existing record
                                    $existingRecord->update([
                                        'value' => $row[$value],
                                        'customer_id' => $row[10],
                                        'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                        'catalog_price_type_id' => $fileValue->catalog_price_type_id,
                                        'core_list' => (trim($row[11]) == 'Contract Pricing') ? 1 : 0,
                                    ]);
                                } else {
                                    // Insert a new record
                                    CatalogPrices::create([
                                        'value' => $row[$value],
                                        'customer_id' => $row[10],
                                        'catalog_item_id' => $catalog_item_id,
                                        'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                        'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                        'catalog_price_type_id' => $fileValue->catalog_price_type_id,
                                        'core_list' => (trim($row[11]) == 'Contract Pricing') ? 1 : 0,
                                    ]);
                                }

                                // CatalogPrices::create([
                                //     'value' => $row[$value],
                                //     'customer_id' => $row[10],
                                //     'catalog_item_id' => $catalog_item_id,
                                //     'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                //     'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                //     'catalog_price_type_id' => $fileValue->catalog_price_type_id,
                                //     'core_list' => (trim($row[11]) == 'Contract Pricing') ? 1 : 0,
                                // ]);

                                CatalogPriceHistory::create([
                                    'value' => $row[$value],
                                    'customer_id' => $row[10],
                                    'catalog_item_id' => $catalog_item_id,
                                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                    'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                    'catalog_price_type_id' => $fileValue->catalog_price_type_id,
                                    'core_list' => (trim($row[11]) == 'Contract Pricing') ? 1 : 0,
                                ]);
                            }
                        }

                        /** For memory optimization we unset the workSheetArray1 and count */
                        unset($workSheetArray1, $count);

                        if (isset($finalInsertArray) && !empty($finalInsertArray)) {
                            try {
                                /** Updating the file upload status */
                                CatalogAttachments::where('id', $fileValue->id)
                                    ->update(['cron' => 5]);

                                /** Inserting the data into the spacific supplier table */
                            } catch (QueryException $e) {
                                /** Handling the QueryException using try catch */
                                Log::error('Error in YourScheduledTask: ' . $e->getMessage());
                                echo "Database insertion failed: " . $e->getMessage();
                            }
                        }

                        /** For memory optimization we unset the excelInsertArray */
                        unset($excelInsertArray);
                    }
                }

                try {
                    /** Update the 'cron' field six after processing done */
                    CatalogAttachments::where('id', $fileValue->id)
                        ->update(['cron' => 6]);

                    $this->info('Uploaded files processed successfully.');
                } catch (QueryException $e) {
                    /** Handling the QueryException using try catch */
                    Log::error('Database updation failed: ' . $e->getMessage());
                    echo "Database updation failed: " . $e->getMessage();
                    die;
                }
            } catch (\Exception $e) {
                /** Handling the Exception using try catch */
                Log::error('Exception loading spreadsheet: ' . $e->getMessage());
                echo "Error loading spreadsheet: " . $e->getMessage();
                die;
            }
        }
    }
}



   // $category_id = ProductDetailsCategory::create([
                                //     'category_name' => $row[$category_name],
                                //     'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                //     'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
                                // ]);

                                // $sub_category_id = ProductDetailsSubCategory::create([
                                //     'category_id' => $category_id,
                                //     'sub_category_name' => $row[$sub_category_name],
                                //     'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                //     'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
                                // ]);

                                // $manufacturer_id = Manufacturer::create([
                                //     'manufacturer_name' => $row[$manufacturer_name],
                                //     'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                //     'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
                                // ]);

                                // $common_attribute_id = ProductDetailsCommonAttribute::create([
                                //     'sub_category_id' => $sub_category_id,
                                //     'attribute_name' => $row[$manufacturer_name],
                                //     'type' => '',
                                //     'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                //     'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
                                // ]);

                                // $catalog_item_id = CatalogItem::create([
                                //     'sku' => $row[$sku],
                                //     'unspsc' => '',
                                //     'active' => '',
                                //     'supplier_id' => $fileValue->supplier_id,
                                //     'industry_id' => '',
                                //     'category_id' => $category_id,
                                //     'sub_category_id' => $sub_category_id,
                                //     'unit_of_measure' => $row[$unit_of_measure],
                                //     'manufacterer_id' => $manufacturer_id,
                                //     'catalog_item_url' => '',
                                //     'catalog_item_name' => '',
                                //     'quantity_per_unit' => '',
                                //     'manufacterer_number' => $row[$manufacterer_number],
                                //     'supplier_shorthand_name' => $row[$supplier_shorthand_name],
                                //     'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                //     'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
                                // ]);