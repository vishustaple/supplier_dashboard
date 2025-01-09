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
        $industryId = 1;

        /** This is the folder path where we save the file */
        $destinationPath = public_path('/excel_sheets');

        /** Select those file name where cron is 11 */
        $fileValue = CatalogAttachments::select('id', 'supplier_id', 'file_name', 'catalog_price_type_id', 'created_by')
            ->where('cron', '=', 11)
            ->whereNull('deleted_by')
            ->first();

        if ($fileValue !== null) {
            $columnValues = CatalogSupplierFields::select(
                'catalog_supplier_fields.label as label',
                'catalog_required_fields.field_name as required_field_column'
            )
            ->leftJoin('catalog_required_fields', 'catalog_supplier_fields.catalog_required_field_id', '=', 'catalog_required_fields.id')
            ->where([
                'deleted' => 0,
                'supplier_id' => $fileValue->supplier_id,
            ])
            ->get();

            /** Convert the collection into an associative array */
            $headerMapping = $columnValues->pluck('required_field_column', 'label')->toArray();

            try {
                /** Increasing the memory limit becouse memory limit issue */
                ini_set('memory_limit', '4G');

                $inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($destinationPath . '/' . $fileValue->file_name);

                if ($inputFileType === 'Xlsx') {
                    $reader = new Xlsx();
                } elseif ($inputFileType === 'Xls') {
                    $reader = new Xls();
                } else {
                    /** throw new Exception('Unsupported file type: ' . $inputFileType); */
                }

                /** Load only the data (without formatting) to save memory */
                $reader->setReadDataOnly(true);

                /** Initialize data array */
                $header = []; /** Initialize header array to store the first row */

                foreach ($reader->load($destinationPath . '/' . $fileValue->file_name, 2)->getActiveSheet()->getRowIterator() as $rowIndex => $row) {
                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(true); /** Only iterate through non-empty cells */
                    
                    $rowData = [];
                    foreach ($cellIterator as $cell) {
                        $rowData[] = trim($cell->getFormattedValue()); /** Collect the cell values */
                    }

                    /** If it's the first row, treat it as the header */
                    if ($rowIndex === 1) {
                        $header = $rowData;  /** This will be your dynamic array2 */
                        continue; /** Skip processing for the header row */
                    }

                    /** Match each row data value to its corresponding header */
                    $matchedRow = [];
                    foreach ($rowData as $index => $value) {
                        if (isset($header[$index])) {
                            $headerValue = $header[$index]; /** Original header value */
                            /** Replace the header with the new mapping if exists */
                            if (isset($headerMapping[$headerValue])) {
                                $matchedRow[$headerMapping[$headerValue]] = $value; /** Map to new header name */
                            } else {
                                $matchedRow[$headerValue] = $value; /** No mapping, keep original */
                            }
                        }
                    }

                    /** Add the matched row to data */
                    /** $data[] = $matchedRow; */

                    if (!empty($matchedRow)) {
                        /** Check if the category exists, if not, create it */
                        $category = ProductDetailsCategory::firstOrCreate(
                            ['category_name' => $matchedRow['category_name']],
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
                                'sub_category_name' => $matchedRow['sub_category_name']
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
                            ['manufacturer_name' => $matchedRow['manufacturer_name']],
                            [
                                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
                            ]
                        );

                        /** Now $manufacturer contains the existing or newly created record */
                        $manufacturer_id = $manufacturer->id;
                        /** Get the last inserted or existing manufacturer id */

                        if ($fileValue->catalog_price_type_id == 2) {
                            /** Check if the common attribute exists, if not, create it */
                            $common_attribute = ProductDetailsCommonAttribute::firstOrCreate(
                                [
                                    'sub_category_id' => $sub_category_id,
                                    'attribute_name' => $matchedRow['manufacturer_name']
                                ],
                                [
                                    'type' => '',
                                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                    'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
                                ]
                            );

                            /** Now $common_attribute contains the existing or newly created record */
                            $common_attribute_id = $common_attribute->id;
                            /** Get the last inserted or existing common attribute id */
                        }

                        /** Check if the catalog item exists by sku, if not, create it */
                        $catalog_item = CatalogItem::firstOrCreate(
                            ['sku' => $matchedRow['sku']],
                            /** Unique condition based on sku */
                            [
                                'unspsc' => '',
                                'industry_id' => $industryId,
                                'category_id' => $category_id,
                                'sub_category_id' => $sub_category_id,
                                'manufacturer_id' => $manufacturer_id,
                                'supplier_id' => $fileValue->supplier_id,
                                'unit_of_measure' => $matchedRow['unit_of_measure'],
                                'catalog_item_url' => '',
                                'catalog_item_name' => '',
                                'quantity_per_unit' => '',
                                'manufacturer_number' => $matchedRow['manufacterer_number'],
                                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                'supplier_shorthand_name' => $matchedRow['supplier_shorthand_name'],
                                'catalog_price_type_id' => $fileValue->catalog_price_type_id,
                            ]
                        );

                        /** Now $catalog_item contains the existing or newly created record */
                        $catalog_item_id = $catalog_item->id;
                        
                        if ($fileValue->catalog_price_type_id == 1) {
                            CheckActive::where([
                                'catalog_item_id' => $catalog_item_id,
                                'catalog_price_type_id' => $fileValue->catalog_price_type_id
                            ])
                            ->update([
                                'active' => 0,
                            ]);
                        }

                        $existingCheckActiveRecord = CheckActive::where('catalog_item_id', $catalog_item_id)->first();

                        if ($existingCheckActiveRecord) {
                            /** Update the existing record */
                            $existingCheckActiveRecord->update([
                                'active' => 1,
                                'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                            ]);
                        } else {
                            /** Create the record if not existing */
                            CheckActive::create([
                                'active' => 1,
                                'catalog_item_id' => $catalog_item_id,
                                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                'catalog_price_type_id' => $fileValue->catalog_price_type_id,
                            ]);
                        }

                        if ($fileValue->catalog_price_type_id == 2) {
                            /** Get the last inserted or existing catalog item id */

                            /** Check if the common value exists, if not, create it */
                            $product_details_common_value = ProductDetailsCommonValue::firstOrCreate(
                                [
                                    'value' => $matchedRow['value'],
                                    'catalog_item_id' => $catalog_item_id,
                                    'common_attribute_id' => $common_attribute_id
                                ],
                                [
                                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                    'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
                                ]
                            );

                            /** Now $product_details_common_value contains the existing or newly created record */
                            $product_details_common_value_id = $product_details_common_value->id;
                            /** Get the last inserted or existing record id */

                            /** Check if the raw value exists, if not, create it */
                            $product_details_raw_value = ProductDetailsRawValue::firstOrCreate(
                                ['catalog_item_id' => $catalog_item_id],
                                [
                                    'raw_values' => '',
                                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                    'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
                                ]
                            );

                            /** Now $product_details_raw_value contains the existing or newly created record */
                            $product_details_raw_value_id = $product_details_raw_value->id; /** Get the last inserted or existing record id */
                        }

                        $existingRecord = CatalogPrices::where('catalog_item_id', $catalog_item_id)->first();

                        if ($existingRecord) {
                            /** Update the existing record */
                            $existingRecord->update([
                                'value' => $matchedRow['value'],
                                // 'customer_id' => $matchedRow['Customer Id'],
                                'customer_id' => 1,
                                'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                'catalog_price_type_id' => $fileValue->catalog_price_type_id,
                                'core_list' => (trim($matchedRow['Pricing Method']) == 'Contract Pricing') ? 1 : 0,
                            ]);
                        } else {
                            /** Insert a new record */
                            CatalogPrices::create([
                                'value' => $matchedRow['value'],
                                // 'customer_id' => $matchedRow['Customer Id'],
                                'customer_id' => 1,
                                'catalog_item_id' => $catalog_item_id,
                                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                'catalog_price_type_id' => $fileValue->catalog_price_type_id,
                                'core_list' => (trim($matchedRow['Pricing Method']) == 'Contract Pricing') ? 1 : 0,
                            ]);
                        }

                        CatalogPriceHistory::create([
                            'value' => $matchedRow['value'],
                            // 'customer_id' => $matchedRow['Customer Id'],
                            'customer_id' => 1,
                            'catalog_item_id' => $catalog_item_id,
                            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                            'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                            'catalog_price_type_id' => $fileValue->catalog_price_type_id,
                            'core_list' => (trim($matchedRow['Pricing Method']) == 'Contract Pricing') ? 1 : 0,
                        ]);
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