<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\{
    CatalogItem,
    Manufacturer,
    CatalogPrices,
    CheckCoreHistory,
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

    protected function divideNumberIntoFourParts($number) {
        $part1 = intval($number * 0.70); /** 70% of the total as an integer */
        $part2 = intval($number * 0.50); /** 50% of the total as an integer */
        $part3 = intval($number * 0.30); /** 30% of the total as an integer */
    
        return [$part1, $part2, $part3];
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $industryId = 1;

        /** This is the folder path where we save the file */
        $destinationPath = public_path('/excel_sheets');

        /** Select those file name where cron is 11 */
        $fileValue = CatalogAttachments::select(
            'id',
            'date',
            'file_name',
            'created_by',
            'supplier_id',
            'catalog_price_type_id',
        )
        ->where('cron', '=', 11)
        ->whereNull('deleted_by')
        ->first();

        if ($fileValue !== null) {
            /** Getting catalog supplier fields names */
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
            $headerMapping1 = $columnValues->pluck('label')->toArray();
            $headerMapping = $columnValues->pluck('required_field_column', 'label')->toArray();
            
            try {
                /** Create a Carbon instance from the date */
                $carbonDate = Carbon::parse($fileValue->date);

                /** Get the year and month */
                $year = $carbonDate->year;
                $month = $carbonDate->month; /** 1 for January, 2 for February, etc. */

                /** Define the month columns (Mapping month number to month name column) */
                $monthColumns = [
                    1 => 'january',
                    2 => 'february',
                    3 => 'march',
                    4 => 'april',
                    5 => 'may',
                    6 => 'june',
                    7 => 'july',
                    8 => 'august',
                    9 => 'september',
                    10 => 'october',
                    11 => 'november',
                    12 => 'december',
                ];
            
                /** Get the column name for the month */
                $monthColumn = $monthColumns[$month];
                
                /** Get records where the year and month match but cron not match */
                $firstFileUploaded = CatalogAttachments::where('cron', '!=', 11)
                ->whereMonth('date', $month)
                ->whereYear('date', $year)
                ->whereNull('deleted_at')
                ->first();

                /** Get records if greater date record exist */
                $greaterDateFileExist = CatalogAttachments::where('cron', '!=', 11)
                ->whereMonth('date', '>', $month)
                ->whereYear('date', '>=', $year)
                ->whereNull('deleted_at')
                ->first();

                /** Check if records were found */
                if ($firstFileUploaded) {
                    CatalogItem::where('supplier_id', $fileValue->supplier_id)
                    ->update([
                        'active' => 0,
                        'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    ]);
                }

                /** Increasing the memory limit becouse memory limit issue */
                ini_set('memory_limit', '4G');

                /** Get file type like xlsx, xls etc. */
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

                /** Reading file using reader */
                $worksheet = $reader->load($destinationPath . '/' . $fileValue->file_name, 2)->getActiveSheet();

                /** Get total rows before the loop and get percent array */
                $percentArray = $this->divideNumberIntoFourParts($worksheet->getHighestRow());

                $header = []; /** Initialize header array to store the first row */

                foreach ($worksheet->getRowIterator() as $rowIndex => $row) {
                    switch ($rowIndex) {
                        case $percentArray[2]:
                            /** Update the catalog attachment table "'cron' => 2" means 30% data uploaded */
                            CatalogAttachments::where('id', $fileValue->id)->update(['cron' => 2]);
                            break;
                        case $percentArray[1]:
                            /** Update the catalog attachment table "'cron' => 4" means 50% data uploaded */
                            CatalogAttachments::where('id', $fileValue->id)->update(['cron' => 4]);
                            break;
                        case $percentArray[0]:
                            /** Update the catalog attachment table "'cron' => 5" means 70% data uploaded */
                            CatalogAttachments::where('id', $fileValue->id)->update(['cron' => 5]);
                            break;
                    }

                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(true); /** Only iterate through non-empty cells */
                    
                    $rowData = []; /** Initialize data array */
                    foreach ($cellIterator as $cell) {
                        $rowData[] = trim($cell->getFormattedValue()); /** Collect the cell values */
                    }

                    /** If it's the first row, treat it as the header */
                    if ($rowIndex === 1) {
                        $header = $rowData;  /** This will be your dynamic array2 */
                        
                        /** Check if all values in $headerMapping1 exist in $header */
                        $difference = array_diff($headerMapping1, $header);

                        /** If column not matching */
                        if (!empty($difference)) {
                            CatalogAttachments::where('id', $fileValue->id)
                            ->update(['cron' => 10]); /** then update "'cron' => 10" means file column not match */
                            
                            echo "The following values are missing in the second array: " . implode(", ", $difference);
                            
                            $this->info('File column not matching.');
                            die;
                        }

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

                    if (!empty($matchedRow) && $matchedRow['value'] !== 0) {
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

                        /** Check if the common attribute exists, if not, create it */
                        if ($fileValue->catalog_price_type_id == 3) {
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

                        
                        $existingCatalogItem = CatalogItem::where([
                            'sku' => $matchedRow['sku'],
                            'supplier_id' => $fileValue->supplier_id,
                        ])
                        ->first();

                        /** Update the existing record */
                        if ($existingCatalogItem) {
                            /** If greater date catalog file not exist then update active data */
                            if (!$greaterDateFileExist) {
                                $existingCatalogItem->update([
                                    'active' => 1,
                                    'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                ]);
                            }

                            /** Now $catalog_item contains the existing or newly created record */
                            $catalog_item_id = $existingCatalogItem->id;
                        } else {
                            /** Check if the catalog item exists by sku, if not, create it */
                            $catalog_item = CatalogItem::firstOrCreate(
                                ['sku' => $matchedRow['sku']],
                                [
                                    'active' => ($greaterDateFileExist) ? 0 : 1,
                                    'unspsc' => '',
                                    'catalog_item_url' => '',
                                    'catalog_item_name' => '',
                                    'quantity_per_unit' => '',
                                    'industry_id' => $industryId,
                                    'category_id' => $category_id,
                                    'sub_category_id' => $sub_category_id,
                                    'manufacturer_id' => $manufacturer_id,
                                    'supplier_id' => $fileValue->supplier_id,
                                    'unit_of_measure' => $matchedRow['unit_of_measure'],
                                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                    'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                    'manufacturer_number' => $matchedRow['manufacterer_number'],
                                    'supplier_shorthand_name' => $matchedRow['supplier_shorthand_name'],
                                ]
                            );

                            /** Now $catalog_item contains the existing or newly created record */
                            $catalog_item_id = $catalog_item->id;
                        }

                        if ($fileValue->catalog_price_type_id == 3) {
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

                        $existingRecord = CatalogPrices::where([
                            'catalog_item_id' => $catalog_item_id,
                            'catalog_price_type_id' => $fileValue->catalog_price_type_id,
                        ])
                        ->first();

                        if ($existingRecord) {
                            /** If greater date catalog file not exist then update value data */
                            if (!$greaterDateFileExist) {
                                /** Update the existing record */
                                $existingRecord->update([
                                    // 'customer_id' => $matchedRow['Customer Id'],
                                    'customer_id' => 1,
                                    'value' => $matchedRow['value'],
                                    'price_file_date' => $fileValue->date,
                                    'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                    'core_list' => (trim($matchedRow['Pricing Method']) == 'Contract Pricing') ? 1 : 0,
                                ]);
                            }
                        } else {
                            /** Insert a new record */
                            CatalogPrices::create([
                                // 'customer_id' => $matchedRow['Customer Id'],
                                'customer_id' => 1,
                                'value' => $matchedRow['value'],
                                'catalog_item_id' => $catalog_item_id,
                                'price_file_date' => $fileValue->date,
                                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                'catalog_price_type_id' => $fileValue->catalog_price_type_id,
                                'core_list' => (trim($matchedRow['Pricing Method']) == 'Contract Pricing') ? 1 : 0,
                            ]);
                        }

                        $priceHistory = CatalogPriceHistory::where('catalog_item_id',  $catalog_item_id) /** Adjust catalog item ID */
                        ->where('catalog_price_type_id', $fileValue->catalog_price_type_id)
                        // ->where($monthColumns[$month], $matchedRow['value']) /** Filter by month */
                        ->where('year', $year) /** Filter by year */
                        ->first(); /** Get the first record for this year */

                        if ($priceHistory) {
                            /** Update the month data */
                            $priceHistory->update([
                                $monthColumn => $matchedRow['value'], /** Update with the new price for that month */
                                'updated_at' => now(),
                            ]);
                        } else {
                            /** If record doesn't exist for the year, you may want to create it */
                            CatalogPriceHistory::create([
                                'year' => $year,
                                'created_at' => now(),
                                'updated_at' => now(),
                                'catalog_item_id' => $catalog_item_id, /** Adjust catalog item ID */
                                $monthColumns[$month] => $matchedRow['value'], /** Set price for that month */
                                'catalog_price_type_id' => $fileValue->catalog_price_type_id, /** Adjust price type ID */
                            ]);
                        }

                        $checkCoreHistory = CheckCoreHistory::where('catalog_item_id', $catalog_item_id) /** Adjust catalog item ID */
                        ->where('catalog_price_type_id', $fileValue->catalog_price_type_id)
                        ->first(); /** Get the first record for this year */

                        if ($checkCoreHistory) {
                            /** If greater date catalog file not exist then update month data */
                            if (!$greaterDateFileExist) {
                                /** Update the month data */
                                $checkCoreHistory->update([
                                    'updated_at' => now(),
                                    'price_file_date' => $fileValue->date,
                                    'core_list' => (trim($matchedRow['Pricing Method']) == 'Contract Pricing') ? 1 : 0,
                                ]);
                            }
                        } else {
                            CheckCoreHistory::create([
                                // 'customer_id' => $matchedRow['Customer Id'],
                                'customer_id' => 1,
                                'catalog_item_id' => $catalog_item_id,
                                'price_file_date' => $fileValue->date,
                                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                'catalog_price_type_id' => $fileValue->catalog_price_type_id,
                                'core_list' => (trim($matchedRow['Pricing Method']) == 'Contract Pricing') ? 1 : 0,
                            ]);
                        }
                    }
                }

                try {
                    /** Update the catalog attachment table "'cron' => 6" means 100% data uploaded */
                    CatalogAttachments::where('id', $fileValue->id)->update(['cron' => 6]);

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