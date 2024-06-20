<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use App\Models\{
    Order,
    Account,
    OrderDetails,
    UploadedFiles
};

class ProcessUploadedFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-uploaded-files';

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

        try{
            /** Select those file name where cron is one */
            $fileValue = DB::table('uploaded_files')->select('id', 'supplier_id', 'file_name', 'start_date', 'end_date', 'created_by')->where('cron', '=', 11)->whereNull('deleted_by')->first();
            
            if ($fileValue !== null) {
                /** Update cron two means start processing data into excel */
                DB::table('uploaded_files')->where('id', $fileValue->id)
                ->update([
                'cron' => UploadedFiles::CRON
                ]);

                /** Add column name here those row you want to skip */
                $skipRowArray = ["Shipto Location Total", "Shipto & Location Total", "TOTAL FOR ALL LOCATIONS", "Total"];
                 
                $columnValues = DB::table('manage_columns')->select('id', 'supplier_id', 'field_name')->where('supplier_id', $fileValue->supplier_id)->get();

                foreach ($columnValues as $key => $value) {
                    if (in_array($value->id, [14, 44, 199])) {
                        $columnArray[$value->supplier_id]['gd_customer_number'] =  $value->field_name;
                    }

                    if (in_array($value->id, [15, 45, 200])) {
                        $columnArray[$value->supplier_id]['gd_customer_name'] = $value->field_name;
                    }

                    if (in_array($value->id, [16, 46, 201])) {
                        $columnArray[$value->supplier_id]['p_customer_number'] = $value->field_name;
                    }

                    if (in_array($value->id, [17, 47, 202])) {
                        $columnArray[$value->supplier_id]['p_customer_name'] = $value->field_name;
                    }

                    // if (in_array($value->id, [1, 18, 48, 264, 125, 148, 203])) {
                    if (in_array($value->id, [1, 18, 48, 297, 125, 148, 203])) {
                        $columnArray[$value->supplier_id]['customer_number'] = $value->field_name;
                    }
                    
                    // if (in_array($value->id, [2, 19, 49, 265, 126, 149, 204])) {
                    if (in_array($value->id, [2, 19, 49, 298, 126, 149, 204])) {
                        $columnArray[$value->supplier_id]['customer_name'] = $value->field_name;
                    }

                    if ($value->supplier_id == 7) {
                        $columnArray[$value->supplier_id]['amount'] = '';
                    }

                    // if (in_array($value->id, [34, 65, 296, 145, 185, 262])) {
                    if (in_array($value->id, [34, 65, 341, 145, 185, 262])) {
                        $columnArray[$value->supplier_id]['amount'] = $value->field_name;
                    }

                    if (in_array($value->supplier_id, [1, 7])) {
                        $columnArray[$value->supplier_id]['invoice_no'] = '';
                    }

                    // if (in_array($value->id, [43, 69, 293, 127, 194])) {
                    if (in_array($value->id, [43, 69, 326, 127, 194])) {
                        $columnArray[$value->supplier_id]['invoice_no'] = $value->field_name;
                    }

                    // if (in_array($value->id, [24, 68, 284, 128, 195, 258])) {
                    if (in_array($value->id, [24, 68, 336, 128, 195, 258])) {
                        $columnArray[$value->supplier_id]['invoice_date'] = $value->field_name;
                    }

                    if (in_array($value->supplier_id, [7])) {
                        $columnArray[$value->supplier_id]['invoice_date'] = '';
                    }

                    $columnArray1[$value->id] = $value->field_name;
                }

                if ($fileValue->supplier_id == 1) {
                    $columnArray2 = [
                        $fileValue->supplier_id => [
                            $columnArray1['1'] => 'sold_to_account',
                            $columnArray1['2'] => 'sold_to_name',
                            $columnArray1['3'] => 'ship_to_account',
                            $columnArray1['4'] => 'ship_to_name',
                            $columnArray1['5'] => 'ship_to_address',
                            $columnArray1['6'] => 'categories',
                            $columnArray1['7'] => 'sub_group_1',
                            $columnArray1['8'] => 'product',
                            $columnArray1['9'] => 'description',
                            $columnArray1['10'] => 'green_y_and_n',
                            $columnArray1['11'] => 'quantity_shipped',
                            $columnArray1['12'] => 'on_core_spend',
                            $columnArray1['13'] => 'off_core_spend',
                            $columnArray1['258'] => 'date',
                            $columnArray1['262'] => 'total_Spend',
                        ]
                    ];
                } elseif ($fileValue->supplier_id == 2) {
                    $columnArray2 = [
                        $fileValue->supplier_id => [
                            $columnArray1['14'] => 'track_code',
                            $columnArray1['15'] => 'track_code_name',
                            $columnArray1['16'] => 'sub_track_code',
                            $columnArray1['17'] => 'sub_track_code_name',
                            $columnArray1['18'] => 'account_number',
                            $columnArray1['19'] => 'account_name',
                            $columnArray1['20'] => 'material',
                            $columnArray1['21'] => 'material_description',
                            $columnArray1['22'] => 'material_segment',
                            $columnArray1['23'] => 'brand_name',
                            $columnArray1['24'] => 'bill_date',
                            $columnArray1['25'] => 'billing_document',
                            $columnArray1['26'] => 'purchase_order_number',
                            $columnArray1['27'] => 'sales_document',
                            $columnArray1['28'] => 'name_of_orderer',
                            $columnArray1['29'] => 'sales_office',
                            $columnArray1['30'] => 'sales_office_name',
                            $columnArray1['31'] => 'bill_line_no',
                            $columnArray1['32'] => 'active_price_point',
                            $columnArray1['33'] => 'billing_qty',
                            $columnArray1['34'] => 'purchase_amount',
                            $columnArray1['35'] => 'freight_billed',
                            $columnArray1['36'] => 'tax_billed',
                            $columnArray1['37'] => 'total_invoice_price',
                            $columnArray1['38'] => 'actual_price_paid',
                            $columnArray1['39'] => 'reference_price',
                            $columnArray1['40'] => 'ext_reference_price',
                            $columnArray1['41'] => 'diff',
                            $columnArray1['42'] => 'discount_percentage',
                            $columnArray1['43'] => 'invoice_number',
                        ]
                    ];
                } elseif ($fileValue->supplier_id == 3) {
                    $columnArray2 = [
                        $fileValue->supplier_id => [
                            $columnArray1['44'] => 'customer_grandparent_id',
                            $columnArray1['45'] => 'customer_grandparent_nm',
                            $columnArray1['46'] => 'customer_parent_id',
                            $columnArray1['47'] => 'customer_parent_nm',
                            $columnArray1['48'] => 'customer_id',
                            $columnArray1['49'] => 'customer_nm',
                            $columnArray1['50'] => 'dept',
                            $columnArray1['51'] => 'class',
                            $columnArray1['52'] => 'subclass',
                            $columnArray1['53'] => 'sku',
                            $columnArray1['54'] => 'manufacture_item',
                            $columnArray1['55'] => 'manufacture_name',
                            $columnArray1['56'] => 'product_description',
                            $columnArray1['57'] => 'core_flag',
                            $columnArray1['58'] => 'maxi_catalog_whole_sale_flag',
                            $columnArray1['59'] => 'uom',
                            $columnArray1['60'] => 'private_brand',
                            $columnArray1['61'] => 'green_shade',
                            $columnArray1['62'] => 'qty_shipped',
                            $columnArray1['63'] => 'unit_net_price',
                            $columnArray1['64'] => 'unit_web_price',
                            $columnArray1['65'] => 'total_spend',
                            $columnArray1['66'] => 'shipto_location',
                            $columnArray1['67'] => 'contact_name',
                            $columnArray1['68'] => 'shipped_date',
                            $columnArray1['69'] => 'invoice',
                            $columnArray1['70'] => 'payment_method', 	
                        ]
                    ];
                } elseif ($fileValue->supplier_id == 4) {
                    $columnArray2 = [
                        $fileValue->supplier_id => [
                            "Group ID1" => 'group_id',
                            $columnArray1['337'] => 'sku',
                            $columnArray1['340'] => 'qty',
                            $columnArray1['323'] => 'eco_id',
                            $columnArray1['334'] => 'sell_uom',
                            $columnArray1['318'] => 'recycled',
                            $columnArray1['312'] => 'diversity',
                            $columnArray1['339'] => 'group_id1',
                            $columnArray1['330'] => 'order_date',
                            $columnArray1['308'] => 'ship_to_zip',
                            $columnArray1['316'] => 'vendor_name',
                            $columnArray1['321'] => 'eco_feature',
                            $columnArray1['327'] => 'on_contract',
                            $columnArray1['306'] => 'ship_to_city',
                            $columnArray1['325'] => 'invoice_date',
                            $columnArray1['332'] => 'order_number',
                            $columnArray1['300'] => 'bill_to_name',
                            $columnArray1['302'] => 'ship_to_name',
                            $columnArray1['336'] => 'shipped_date',
                            $columnArray1['328'] => 'order_contact',
                            $columnArray1['317'] => 'recycled_flag',
                            $columnArray1['307'] => 'ship_to_state',
                            $columnArray1['313'] => 'diversity_code',
                            $columnArray1['301'] => 'ship_to_number',
                            $columnArray1['299'] => 'bill_to_number',
                            $columnArray1['342'] => 'avg_sell_price',
                            $columnArray1['326'] => 'invoice_number',
                            $columnArray1['341'] => 'adj_gross_sales',
                            $columnArray1['322'] => 'eco_sub_feature',
                            $columnArray1['335'] => 'ship_to_contact',
                            $columnArray1['310'] => 'item_description',
                            $columnArray1['309'] => 'vendor_part_number',
                            $columnArray1['324'] => 'budget_center_name',
                            $columnArray1['333'] => 'payment_method_code1',
                            $columnArray1['298'] => 'master_customer_name',
                            "Payment Method Code1" => 'payment_method_code',
                            $columnArray1['303'] => 'ship_to_line1_address',
                            $columnArray1['314'] => 'diversity_sub_type_cd',
                            $columnArray1['304'] => 'ship_to_line2_address',
                            $columnArray1['305'] => 'ship_to_line3_address',
                            $columnArray1['297'] => 'master_customer_number',
                            $columnArray1['331'] => 'order_method_description',
                            $columnArray1['315'] => 'selling_unit_measure_qty',
                            $columnArray1['311'] => 'primary_product_hierarchy',
                            $columnArray1['338'] => 'transaction_source_system1',
                            $columnArray1['329'] => 'order_contact_phone_number',
                            $columnArray1['319'] => 'product_post_consumer_content',
                            $columnArray1['320'] => 'remanufactured_refurbished_flag',
                            "Transaction Source System1" => 'transaction_source_system',

                            // $columnArray1['263'] => 'div_id',
                            // $columnArray1['264'] => 'master_customer_number_id',
                            // $columnArray1['265'] => 'master_customer_name_id',
                            // $columnArray1['266'] => 'bill_to_number_id',
                            // $columnArray1['267'] => 'bill_to_name_id',
                            // $columnArray1['268'] => 'ship_to_number_id',
                            // $columnArray1['269'] => 'ship_to_name_id',
                            // $columnArray1['270'] => 'ship_to_line1_address_id',
                            // $columnArray1['271'] => 'ship_to_line2_address_id',
                            // $columnArray1['272'] => 'ship_to_line3_address_id',
                            // $columnArray1['273'] => 'ship_to_city_id',
                            // $columnArray1['274'] => 'ship_to_state_id',
                            // $columnArray1['275'] => 'ship_to_zip_id',
                            // $columnArray1['276'] => 'primary_product_hierarchy_desc',
                            // $columnArray1['277'] => 'sku_id',
                            // $columnArray1['278'] => 'item_description_id',
                            // $columnArray1['279'] => 'vendor_name_id',
                            // $columnArray1['280'] => 'vendor_part_number_id',
                            // $columnArray1['281'] => 'sell_uom_id',
                            // $columnArray1['282'] => 'selling_unit_measure_qty_id',
                            // $columnArray1['283'] => 'order_date_id',
                            // $columnArray1['284'] => 'shipped_date_id',
                            // $columnArray1['285'] => 'order_number_id',
                            // $columnArray1['286'] => 'order_contact_id',
                            // $columnArray1['287'] => 'order_contact_phone_number_id',
                            // $columnArray1['288'] => 'order_method_code_id',
                            // $columnArray1['289'] => 'order_method_description_id',
                            // $columnArray1['290'] => 'transaction_source_system_desc',
                            // $columnArray1['291'] => 'sku_type_id',
                            // $columnArray1['292'] => 'on_contract_id',
                            // $columnArray1['293'] => 'invoice_number_id',
                            // $columnArray1['294'] => 'invoice_date_id',
                            // $columnArray1['295'] => 'qty',
                            // $columnArray1['296'] => 'adj_gross_sales',
                        ]
                    ];
                } elseif ($fileValue->supplier_id == 5) {
                    $columnArray2 = [
                        $fileValue->supplier_id => [
                            $columnArray1['124'] => 'sales_id',
                            $columnArray1['125'] => 'customer_num',
                            $columnArray1['126'] => 'customer_name',
                            $columnArray1['127'] => 'invoice_num',
                            $columnArray1['128'] => 'invoice_date',
                            $columnArray1['129'] => 'po_number',
                            $columnArray1['130'] => 'cost_center_code',
                            $columnArray1['131'] => 'cost_center_value',
                            $columnArray1['132'] => 'dlv_name',
                            $columnArray1['133'] => 'dlv_street',
                            $columnArray1['134'] => 'dlv_city',
                            $columnArray1['135'] => 'dlv_state',
                            $columnArray1['136'] => 'dlv_zip',
                            $columnArray1['137'] => 'item_num',
                            $columnArray1['138'] => 'item_name',
                            $columnArray1['139'] => 'category',
                            $columnArray1['140'] => 'category_umbrella',
                            $columnArray1['141'] => 'price_method',
                            $columnArray1['142'] => 'uom',
                            $columnArray1['143'] => 'current_list',
                            $columnArray1['144'] => 'qty',
                            $columnArray1['145'] => 'ext_price',
                            $columnArray1['146'] => 'line_tax',
                            $columnArray1['147'] => 'line_total',
                            $columnArray1['259'] => 'price', 
                        ]
                    ];
                } elseif ($fileValue->supplier_id == 6) {
                    $columnArray2 = [
                        $fileValue->supplier_id => [
                            $columnArray1['148'] => 'payer',
                            $columnArray1['149'] => 'name_payer',
                            $columnArray1['150'] => 'sold_to_pt',
                            $columnArray1['151'] => 'name_sold_to_party',
                            $columnArray1['152'] => 'ship_to',
                            $columnArray1['153'] => 'name_ship_to',
                            $columnArray1['154'] => 'name_3_plus_name_4_ship_to',
                            $columnArray1['155'] => 'street_ship_to',
                            $columnArray1['156'] => 'district_ship_to',
                            $columnArray1['157'] => 'postalcode_ship_to',
                            $columnArray1['158'] => 'city_ship_to',
                            $columnArray1['159'] => 'country_ship_to',
                            $columnArray1['160'] => 'leader_customer_1',
                            $columnArray1['161'] => 'leader_customer_2',
                            $columnArray1['162'] => 'leader_customer_3',
                            $columnArray1['163'] => 'leader_customer_4',
                            $columnArray1['164'] => 'leader_customer_5',
                            $columnArray1['165'] => 'leader_customer_6',
                            $columnArray1['166'] => 'product_hierarchy',
                            $columnArray1['167'] => 'section',
                            $columnArray1['168'] => 'family',
                            $columnArray1['169'] => 'category',
                            $columnArray1['170'] => 'sub_category',
                            $columnArray1['171'] => 'material',
                            $columnArray1['172'] => 'material_description',
                            $columnArray1['173'] => 'ownbrand',
                            $columnArray1['174'] => 'green_product',
                            $columnArray1['175'] => 'nbs',
                            $columnArray1['176'] => 'customer_material',
                            $columnArray1['177'] => 'customer_description',
                            $columnArray1['178'] => 'sales_unit',
                            $columnArray1['179'] => 'qty_in_sku',
                            $columnArray1['180'] => 'sales_deal',
                            $columnArray1['181'] => 'purchase_order_type',
                            $columnArray1['182'] => 'qty_in_sales_unit_p',
                            $columnArray1['183'] => 'quantity_in_sku_p',
                            $columnArray1['184'] => 'number_of_orders_p',
                            $columnArray1['185'] => 'sales_amount_p',
                            $columnArray1['186'] => 'tax_amount_p',
                            $columnArray1['187'] => 'net_sales_p',
                            $columnArray1['188'] => 'avg_selling_price_p',
                            $columnArray1['189'] => 'document_date',
                            $columnArray1['190'] => 'sales_document',
                            $columnArray1['191'] => 'po_number',
                            $columnArray1['192'] => 'bpo_number',
                            $columnArray1['193'] => 'invoice_list',
                            $columnArray1['194'] => 'billing_document',
                            $columnArray1['195'] => 'billing_date',
                            $columnArray1['196'] => 'cac_number',
                            $columnArray1['197'] => 'cac_description',
                            $columnArray1['198'] => 'billing_month_p', 
                        ]
                    ];
                } elseif ($fileValue->supplier_id == 7) {
                    $columnArray2 = [
                        $fileValue->supplier_id => [
                            $columnArray1['199'] => 'gp_id',
                            $columnArray1['200'] => 'gp_name',
                            $columnArray1['201'] => 'parent_id',
                            $columnArray1['202'] => 'parent_name',
                            $columnArray1['203'] => 'account_id',
                            $columnArray1['204'] => 'account_name',
                            $columnArray1['205'] => 'year_01',
                            $columnArray1['206'] => 'year_02',
                            $columnArray1['207'] => 'year_03',
                            $columnArray1['208'] => 'year_04',
                            $columnArray1['209'] => 'year_05',
                            $columnArray1['210'] => 'year_06',
                            $columnArray1['211'] => 'year_07',
                            $columnArray1['212'] => 'year_08',
                            $columnArray1['213'] => 'year_09',
                            $columnArray1['214'] => 'year_10',
                            $columnArray1['215'] => 'year_11',
                            $columnArray1['216'] => 'year_12',
                            $columnArray1['217'] => 'year_13',
                            $columnArray1['218'] => 'year_14',
                            $columnArray1['219'] => 'year_15',
                            $columnArray1['220'] => 'year_16',
                            $columnArray1['221'] => 'year_17',
                            $columnArray1['222'] => 'year_18',
                            $columnArray1['223'] => 'year_19',
                            $columnArray1['224'] => 'year_20',
                            $columnArray1['225'] => 'year_21',
                            $columnArray1['226'] => 'year_22',
                            $columnArray1['227'] => 'year_23',
                            $columnArray1['228'] => 'year_24',
                            $columnArray1['229'] => 'year_25',
                            $columnArray1['230'] => 'year_26',
                            $columnArray1['231'] => 'year_27',
                            $columnArray1['232'] => 'year_28',
                            $columnArray1['233'] => 'year_29',
                            $columnArray1['234'] => 'year_30',
                            $columnArray1['235'] => 'year_31',
                            $columnArray1['236'] => 'year_32',
                            $columnArray1['237'] => 'year_33',
                            $columnArray1['238'] => 'year_34',
                            $columnArray1['239'] => 'year_35',
                            $columnArray1['240'] => 'year_36',
                            $columnArray1['242'] => 'year_37',
                            $columnArray1['243'] => 'year_38',
                            $columnArray1['244'] => 'year_39',
                            $columnArray1['245'] => 'year_40',
                            $columnArray1['246'] => 'year_41',
                            $columnArray1['247'] => 'year_42',
                            $columnArray1['248'] => 'year_43',
                            $columnArray1['249'] => 'year_44',
                            $columnArray1['250'] => 'year_45',
                            $columnArray1['251'] => 'year_46',
                            $columnArray1['252'] => 'year_47',
                            $columnArray1['253'] => 'year_48',
                            $columnArray1['254'] => 'year_49',
                            $columnArray1['255'] => 'year_50',
                            $columnArray1['256'] => 'year_51',
                            $columnArray1['257'] => 'year_52',
                        ]
                    ];
                } else {

                }

                try {
                    /** Increasing the memory limit becouse memory limit issue */
                    ini_set('memory_limit', '1024M');

                    /** Inserting files data into the database after doing excel import */
                        $weeklyCheck = true;
                       
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
                        $sheetCount = $spreadSheet->getSheetCount(); /** Getting sheet count for run loop on index */
                        
                        if ($fileValue->supplier_id == 4 || $fileValue->supplier_id == 3) {
                            $sheetCount = ($sheetCount > 1) ? $sheetCount - 2 : $sheetCount; /** Handle case if sheet count is one */
                        } else {
                            $sheetCount = ($sheetCount > 1) ? $sheetCount - 1 : $sheetCount;
                        }

                        $supplierFilesNamesArray = [
                            1 => 'Usage By Location and Item',
                            2 => 'Invoice Detail Report',
                            // 3 => '',
                            4 => 'All Shipped Order Detail',
                            5 => 'Centerpoint_Summary_Report',
                            6 => 'Blad1',
                            7 => 'Weekly Sales Account Summary', 
                        ];

                        DB::table('uploaded_files')
                        ->where('id', $fileValue->id)
                        ->update([
                            'cron' => 4
                        ]);

                        for ($i = 0; $i <= $sheetCount; $i++) {
                            $count = $maxNonEmptyCount = 0;
                            
                            // if ($fileValue->supplier_id == 5 && $i == 1) {
                            //     continue;
                            // }
                            if (($sheetCount == 1 && $i == 1 && $fileValue->supplier_id != 5) || ($fileValue->supplier_id == 5 && $i == 0) || ($fileValue->supplier_id == 7 && in_array($i, [0, 1, 3, 4, 5, 6, 7]))) {
                                continue;
                            }

                            if ($fileValue->supplier_id != 3) {
                                $sheet = $spreadSheet->getSheetByName($supplierFilesNamesArray[$fileValue->supplier_id]);
                            }
                
                            if (isset($sheet) && $sheet) {
                                $workSheetArray = $sheet->toArray();
                            } else {
                                $workSheetArray = $spreadSheet->getSheet($i)->toArray(); /** Getting worksheet using index */
                            }

                            foreach ($workSheetArray as $key=>$values) {
                                /** Checking not empty columns */
                                $nonEmptyCount = count(array_filter(array_values($values), function ($item) {
                                    return !empty($item);
                                }));
                                
                                /** If column count is greater then previous row columns count. Then assigen value to '$maxNonEmptyvalue' */
                                if ($nonEmptyCount > $maxNonEmptyCount) {
                                    $maxNonEmptyValue = $values;
                                    $startIndexValueArray = $key;
                                    $maxNonEmptyCount = $nonEmptyCount;
                                } 
                                
                                /** Stop loop after reading 31 rows from excel file */
                                if ($key > 20) {
                                    break;
                                }
                            }

                            if ($fileValue->supplier_id == 4) {
                                $maxNonEmptyValue[36] = "Payment Method Code1";
                                $maxNonEmptyValue[37] = "Payment Method Code";
                                $maxNonEmptyValue[42] = "Transaction Source System1";
                                $maxNonEmptyValue[43] = "Transaction Source System";
                                $maxNonEmptyValue[44] = "Group ID1";
                                $maxNonEmptyValue[45] = "Group ID";
                            }

                            // dd($maxNonEmptyValue, $columnArray2);
                            /** Clean up the values */
                            $maxNonEmptyValue = array_map(function ($value) {
                                /** Remove line breaks and trim whitespace */
                                return str_replace(["\r", "\n"], '', $value);
                            }, $maxNonEmptyValue);

                            if ($fileValue->supplier_id == 7) {
                                $weeklyPriceColumnArray = [];
                                foreach ($maxNonEmptyValue as $key => $value) {
                                    if ($key >= 6) {
                                        $weeklyPriceColumnArray[$key] = $value;
                                        // $weeklyArrayKey++;
                                    }
                                }
                            }

                            /** Unset the "$maxNonEmptyCount" for memory save */
                            unset($maxNonEmptyCount);

                            $startIndex = $startIndexValueArray; /** Specify the starting index for get the excel column value */

                            /** Unset the "$startIndexValueArray" for memory save */
                            unset($startIndexValueArray);

                            if ($fileValue->supplier_id == 2) {
                               $graingerCount = $startIndex + 1;
                            }
                         
                            foreach ($workSheetArray as $key => $row) {
                                if($key > $startIndex){
                                    $workSheetArray1[] = $row;
                                    if (!empty($columnArray[$fileValue->supplier_id]['gd_customer_number'])) {
                                        $keyGrandParent = array_search($columnArray[$fileValue->supplier_id]['gd_customer_number'], $maxNonEmptyValue);
                                    }

                                    if (!empty($columnArray[$fileValue->supplier_id]['p_customer_number'])) {
                                        $keyParent = array_search($columnArray[$fileValue->supplier_id]['p_customer_number'], $maxNonEmptyValue);
                                    }

                                    if (!empty($columnArray[$fileValue->supplier_id]['customer_number'])) {
                                        $keyCustomer = array_search($columnArray[$fileValue->supplier_id]['customer_number'], $maxNonEmptyValue);
                                    }

                                    if (!empty($columnArray[$fileValue->supplier_id]['gd_customer_name'])) {
                                        $keyGrandParentName = array_search($columnArray[$fileValue->supplier_id]['gd_customer_name'], $maxNonEmptyValue);
                                    }

                                    if (!empty($columnArray[$fileValue->supplier_id]['p_customer_name'])) {
                                        $keyParentName = array_search($columnArray[$fileValue->supplier_id]['p_customer_name'], $maxNonEmptyValue);
                                    }

                                    if (!empty($columnArray[$fileValue->supplier_id]['customer_name'])) {
                                        $keyCustomerName = array_search($columnArray[$fileValue->supplier_id]['customer_name'], $maxNonEmptyValue);
                                    }

                                    if (($fileValue->supplier_id == 2 && $key > $graingerCount) || $fileValue->supplier_id == 3 || $fileValue->supplier_id == 7) {
                                        if (isset($row[$keyCustomer]) && !empty($row[$keyCustomer])) {
                                            $customers = Account::where('account_number', 'LIKE', '%' . ltrim($row[$keyCustomer], '0') . '%')->first();
                                            if (empty($customers)) {
                                                if (strpos($row[$keyParentName], "CenterPoint") !== false) {
                                                    Account::create([
                                                        'parent_id' => $row[$keyParent],
                                                        'parent_name' => $row[$keyParentName],
                                                        'account_number' => $row[$keyCustomer],
                                                        'account_name' => $row[$keyCustomerName],
                                                        'customer_name' => $row[$keyCustomerName],
                                                        'grandparent_id' => $row[$keyGrandParent],
                                                        'category_supplier' => (($fileValue->supplier_id == 7) ? (3) : ($fileValue->supplier_id)) ,
                                                        'grandparent_name' => $row[$keyGrandParentName],
                                                    ]);
                                                } else {
                                                    Account::create([
                                                        'parent_id' => $row[$keyParent],
                                                        'parent_name' => $row[$keyParentName],
                                                        'account_number' => $row[$keyCustomer],
                                                        'customer_name' => $row[$keyCustomerName],
                                                        'grandparent_id' => $row[$keyGrandParent],
                                                        'category_supplier' => (($fileValue->supplier_id == 7) ? (3) : ($fileValue->supplier_id)) ,
                                                        'grandparent_name' => $row[$keyGrandParentName],
                                                    ]);
                                                }
                                            } else {
                                                Account::where('account_number', 'LIKE', '%' . ltrim($row[$keyCustomer], '0') . '%')->update([
                                                    'parent_id' => $row[$keyParent],
                                                    'parent_name' => $row[$keyParentName],
                                                    'account_number' => ltrim($row[$keyCustomer], '0'),
                                                    'customer_name' => $row[$keyCustomerName],
                                                    'grandparent_id' => $row[$keyGrandParent],
                                                    'category_supplier' => (($fileValue->supplier_id == 7) ? (3) : ($fileValue->supplier_id)),
                                                    'grandparent_name' => $row[$keyGrandParentName],
                                                ]);
                                            }
                                        }
                                    }

                                    if (in_array($fileValue->supplier_id, [1, 4, 5, 6])) {
                                        if (isset($row[$keyCustomer]) && !empty($row[$keyCustomer])) {
                                            $customers = Account::where('account_number', 'LIKE', '%' . ltrim($row[$keyCustomer], '0') . '%')->first();
                                            if (empty($customers)) {
                                                Account::create([
                                                    'account_number' => ltrim($row[$keyCustomer], '0'),
                                                    'customer_name' => $row[$keyCustomerName],
                                                    'category_supplier' => $fileValue->supplier_id,
                                                ]);
                                            } else {
                                                Account::where('account_number', 'LIKE', '%' . ltrim($row[$keyCustomer], '0') . '%')->update([
                                                    'account_number' => ltrim($row[$keyCustomer], '0'),
                                                    'customer_name' => $row[$keyCustomerName],
                                                    'category_supplier' => $fileValue->supplier_id,
                                                ]);
                                            }
                                        }
                                    }
                                }
                            }

                            if (isset($workSheetArray1) && !empty($workSheetArray1)) {
                                /** For insert data into the database */
                                foreach ($workSheetArray1 as $key => $row) {
                                    if (count(array_intersect($skipRowArray, $row)) <= 0) {
                                        if (!empty($columnArray[$fileValue->supplier_id]['customer_number'])) {
                                            $keyCustomerNumber = array_search($columnArray[$fileValue->supplier_id]['customer_number'], $maxNonEmptyValue);
                                        }
    
                                        if (!empty($columnArray[$fileValue->supplier_id]['amount'])) {
                                            $keyAmount = array_search($columnArray[$fileValue->supplier_id]['amount'], $maxNonEmptyValue);
                                        }
    
                                        if (!empty($columnArray[$fileValue->supplier_id]['invoice_no'])) {
                                            $keyInvoiceNumber = array_search($columnArray[$fileValue->supplier_id]['invoice_no'], $maxNonEmptyValue);
                                        }
    
                                        if (!empty($columnArray[$fileValue->supplier_id]['invoice_date'])) {
                                            $keyInvoiceDate = array_search($columnArray[$fileValue->supplier_id]['invoice_date'], $maxNonEmptyValue);
                                        }
     
                                        if (isset($keyCustomerNumber) && !empty($row[$keyCustomerNumber])) {
                                            foreach ($row as $key1 => $value) {
                                                if(!empty($maxNonEmptyValue[$key1])) {
                                                    if ($fileValue->supplier_id == 1) {
                                                        if ($columnArray2[$fileValue->supplier_id][trim($maxNonEmptyValue[$key1])] == 'date') {
                                                            $excelInsertArray[$key][$columnArray2[$fileValue->supplier_id][trim($maxNonEmptyValue[$key1])]] =  (!empty($value)) ? Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value))->format('Y-m-d H:i:s') : ('');
                                                        } else {
                                                            $excelInsertArray[$key][$columnArray2[$fileValue->supplier_id][trim($maxNonEmptyValue[$key1])]] = $value;
                                                        }
                                                    } elseif ($fileValue->supplier_id == 2) {
                                                        if ($columnArray2[$fileValue->supplier_id][trim($maxNonEmptyValue[$key1])] == 'bill_date') {
                                                            $excelInsertArray[$key][$columnArray2[$fileValue->supplier_id][trim($maxNonEmptyValue[$key1])]] = (!empty($value)) ? Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value))->format('Y-m-d H:i:s') : ('');
                                                        } else {
                                                            $excelInsertArray[$key][$columnArray2[$fileValue->supplier_id][trim($maxNonEmptyValue[$key1])]] = $value;
                                                        }
                                                    } elseif ($fileValue->supplier_id == 3) {
                                                        if ($columnArray2[$fileValue->supplier_id][trim($maxNonEmptyValue[$key1])] == 'shipped_date') {
                                                            $excelInsertArray[$key][$columnArray2[$fileValue->supplier_id][trim($maxNonEmptyValue[$key1])]] = (!empty($value)) ? Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value))->format('Y-m-d H:i:s') : ('');
                                                        } else {
                                                            $excelInsertArray[$key][$columnArray2[$fileValue->supplier_id][trim($maxNonEmptyValue[$key1])]] = $value;
                                                        }
                                                    } elseif ($fileValue->supplier_id == 4) {   
                                                        if ($columnArray2[$fileValue->supplier_id][trim($maxNonEmptyValue[$key1])] == 'shipped_date') {
                                                            $excelInsertArray[$key][$columnArray2[$fileValue->supplier_id][trim($maxNonEmptyValue[$key1])]] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[$keyInvoiceDate]))->format('Y-m-d H:i:s');
                                                        } else {
                                                            $excelInsertArray[$key][$columnArray2[$fileValue->supplier_id][trim($maxNonEmptyValue[$key1])]] = $value;
                                                        }
                                                    } elseif ($fileValue->supplier_id == 5) {
                                                        if ($columnArray2[$fileValue->supplier_id][trim($maxNonEmptyValue[$key1])] == 'invoice_date') {
                                                            $excelInsertArray[$key][$columnArray2[$fileValue->supplier_id][trim($maxNonEmptyValue[$key1])]] = (!empty($value)) ? Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value))->format('Y-m-d H:i:s') : ('');
                                                        } else {
                                                            $excelInsertArray[$key][$columnArray2[$fileValue->supplier_id][trim($maxNonEmptyValue[$key1])]] = $value;
                                                        }
                                                    } elseif ($fileValue->supplier_id == 6) {
                                                        if ($columnArray2[$fileValue->supplier_id][trim($maxNonEmptyValue[$key1])] == 'billing_date') {
                                                            $excelInsertArray[$key][$columnArray2[$fileValue->supplier_id][trim($maxNonEmptyValue[$key1])]] = (!empty($value)) ? Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value))->format('Y-m-d H:i:s') : ('');
                                                        } else {
                                                            $excelInsertArray[$key][$columnArray2[$fileValue->supplier_id][trim($maxNonEmptyValue[$key1])]] = $value;
                                                        }
                                                    } elseif ($fileValue->supplier_id == 7) {
                                                        if ($key1 < 6) {
                                                            $excelInsertArray[$key][$columnArray2[$fileValue->supplier_id][trim($maxNonEmptyValue[$key1])]] = $value;
                                                        } else {
                                                            $excelInsertArray[$key][$columnArray2[$fileValue->supplier_id][trim("Year_" . substr($maxNonEmptyValue[$key1], - 2))]] = $value;
                                                        }
                                                    } else {

                                                    }
                                                    
                                                    $excelInsertArray[$key]['data_id'] = $fileValue->id;
                                                    $excelInsertArray[$key]['created_at'] = Carbon::now()->format('Y-m-d H:i:s');
                                                    $excelInsertArray[$key]['updated_at'] = Carbon::now()->format('Y-m-d H:i:s');

                                                    $finalInsertArray[] = [
                                                        'data_id' => $fileValue->id,
                                                        'value' => $value,
                                                        'key' => $maxNonEmptyValue[$key1],
                                                        'file_name' => $fileValue->file_name,
                                                        'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                        'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                    ];  
                                                }
                                            }
                                            
                                            if ($fileValue->supplier_id == 7) {
                                                foreach ($weeklyPriceColumnArray as $key => $value) {
                                                    if (!empty($row[$key])) {                                                    
                                                        $date = explode("-", $workSheetArray[7][$key]);
    
                                                        $orderLastInsertId = Order::create([
                                                            'data_id' => $fileValue->id,
                                                            'created_by' => $fileValue->created_by,
                                                            'supplier_id' => (($fileValue->supplier_id == 7) ? (3) : ($fileValue->supplier_id)),
                                                            'amount' => str_replace(",", "", number_format($row[$key], 2, '.')),
                                                            'date' =>  (!empty($keyInvoiceDate) && !empty($row[$keyInvoiceDate])) ? (($row[$keyInvoiceDate] && $fileValue->supplier_id == 4) ? (Carbon::createFromFormat('Y-m-d H:i:s', $row[$keyInvoiceDate])->format('Y-m-d H:i:s')) : (Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[$keyInvoiceDate]))->format('Y-m-d H:i:s'))) : ($fileValue->start_date),
                                                            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                            'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                            'customer_number' => (!empty($keyCustomerNumber) && !empty($row[$keyCustomerNumber])) ? (ltrim($row[$keyCustomerNumber], "0")) : (''),
                                                        ]);
    
                                                        if ($weeklyCheck) {
                                                            OrderDetails::create([
                                                                'data_id' => $fileValue->id,
                                                                'order_id' => $orderLastInsertId->id,
                                                                'created_by' => $fileValue->created_by,
                                                                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                                'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                                'invoice_date' => (!empty($keyInvoiceDate) && !empty($row[$keyInvoiceDate])) ? (($row[$keyInvoiceDate] && $fileValue->supplier_id == 4) ? (Carbon::createFromFormat('Y-m-d H:i:s', $row[$keyInvoiceDate])->format('Y-m-d H:i:s')) : (Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[$keyInvoiceDate]))->format('Y-m-d H:i:s'))) : ($fileValue->start_date),
                                                                'invoice_number' => (!empty($keyInvoiceNumber) && !empty($row[$keyInvoiceNumber])) ? ($row[$keyInvoiceNumber]) : (OrderDetails::randomInvoiceNum()),
                                                                'order_file_name' => $fileValue->supplier_id."_weekly_".date_format(date_create($fileValue->start_date),"Y/m"),
                                                            ]);
                                                        } else {
                                                            OrderDetails::create([
                                                                'data_id' => $fileValue->id,
                                                                'order_id' => $orderLastInsertId->id,
                                                                'created_by' => $fileValue->created_by,
                                                                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                                'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                                'invoice_number' => (!empty($keyInvoiceNumber) && !empty($row[$keyInvoiceNumber])) ? ($row[$keyInvoiceNumber]) : (OrderDetails::randomInvoiceNum()),
                                                                'invoice_date' => (!empty($keyInvoiceDate) && !empty($row[$keyInvoiceDate])) ? (($row[$keyInvoiceDate] && $fileValue->supplier_id == 4) ? (Carbon::createFromFormat('Y-m-d H:i:s', $row[$keyInvoiceDate])->format('Y-m-d H:i:s')) : (Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[$keyInvoiceDate]))->format('Y-m-d H:i:s'))) : ($fileValue->start_date),
                                                                'order_file_name' => $fileValue->supplier_id."_monthly_".date_format(date_create($fileValue->start_date),"Y/m"),
                                                            ]);
                                                        }
                                                    }
                                                }
                                            } else {
                                                if ($fileValue->supplier_id == 6) {
                                                    $customerNumber = explode(" ", $row[$keyCustomerNumber]);
                                                    $orderLastInsertId = Order::create([
                                                        'data_id' => $fileValue->id,
                                                        'created_by' => $fileValue->created_by,
                                                        'supplier_id' => $fileValue->supplier_id,
                                                        'amount' => $row[$keyAmount],
                                                        'date' =>  (isset($keyInvoiceDate) && !empty($row[$keyInvoiceDate])) ? (($row[$keyInvoiceDate] && $fileValue->supplier_id == 4) ? (Carbon::createFromFormat('Y-m-d H:i:s', $row[$keyInvoiceDate])->format('Y-m-d H:i:s')) : (Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[$keyInvoiceDate]))->format('Y-m-d H:i:s'))) : ($fileValue->start_date),
                                                        'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                        'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                        'customer_number' => $customerNumber[0],
                                                    ]);
                                                } else {  
                                                    $orderLastInsertId = Order::create([
                                                        'data_id' => $fileValue->id,
                                                        'created_by' => $fileValue->created_by,
                                                        'supplier_id' => $fileValue->supplier_id,
                                                        'amount' => $row[$keyAmount],
                                                        'date' => (isset($keyInvoiceDate) && !empty($row[$keyInvoiceDate])) ? (Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[$keyInvoiceDate]))->format('Y-m-d H:i:s')) : ($fileValue->start_date),
                                                        'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                        'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                        'customer_number' => ltrim($row[$keyCustomerNumber], '0'),
                                                    ]);
                                                }
    
                                                if ($weeklyCheck) {
                                                    $orderDetailsArray[] = [
                                                        'data_id' => $fileValue->id,
                                                        'order_id' => $orderLastInsertId->id,
                                                        'created_by' => $fileValue->created_by,
                                                        'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                        'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                        'invoice_date' => (isset($keyInvoiceDate) && !empty($row[$keyInvoiceDate])) ? (Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[$keyInvoiceDate]))->format('Y-m-d H:i:s')) : ($fileValue->start_date),
                                                        'invoice_number' => (isset($keyInvoiceNumber) && !empty($row[$keyInvoiceNumber])) ? ($row[$keyInvoiceNumber]) : (OrderDetails::randomInvoiceNum()),
                                                        'order_file_name' => $fileValue->supplier_id."_weekly_".date_format(date_create($fileValue->start_date),"Y/m"),
                                                    ];
                                                } else {
                                                    $orderDetailsArray[] = [
                                                        'data_id' => $fileValue->id,
                                                        'order_id' => $orderLastInsertId->id,
                                                        'created_by' => $fileValue->created_by,
                                                        'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                        'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                        'invoice_number' => (isset($keyInvoiceNumber) && !empty($row[$keyInvoiceNumber])) ? ($row[$keyInvoiceNumber]) : (OrderDetails::randomInvoiceNum()),
                                                        'invoice_date' => (isset($keyInvoiceDate) && !empty($row[$keyInvoiceDate])) ? (Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[$keyInvoiceDate]))->format('Y-m-d H:i:s')) : ($fileValue->start_date),
                                                        'order_file_name' => $fileValue->supplier_id."_monthly_".date_format(date_create($fileValue->start_date),"Y/m"),
                                                    ];
                                                }
                                            }
                                            // dd($excelInsertArray);

                                            foreach ($finalInsertArray as &$item) {
                                                if (!isset($item['order_id']) && empty($item['order_id'])) {
                                                    $item['order_id'] = $orderLastInsertId->id;
                                                }
                                            }
                                            if ($count == 70) {
                                                $count = 0;
                                                try {
                                                    if ($fileValue->supplier_id == 1) {
                                                        DB::table('g_and_t_laboratories_charles_river_order')->insert($excelInsertArray);
                                                    } elseif ($fileValue->supplier_id == 2) {
                                                        DB::table('grainger_order')->insert($excelInsertArray);
                                                    } elseif ($fileValue->supplier_id == 3) {
                                                        DB::table('office_depot_order')->insert($excelInsertArray);
                                                    } elseif ($fileValue->supplier_id == 4) {   
                                                        // DB::table('staples_order')->insert($excelInsertArray);
                                                        DB::table('staples_orders_data')->insert($excelInsertArray);
                                                    } elseif ($fileValue->supplier_id == 5) {
                                                        DB::table('wb_mason_order')->insert($excelInsertArray);
                                                    } elseif ($fileValue->supplier_id == 6) {
                                                        DB::table('lyreco_order')->insert($excelInsertArray);
                                                    } elseif ($fileValue->supplier_id == 7) {
                                                        DB::table('odp_order')->insert($excelInsertArray);
                                                    } else {

                                                    }

                                                    if ($fileValue->supplier_id != 7) {
                                                        DB::table('order_details')->insert($orderDetailsArray);
                                                    }
                                                    DB::table('order_product_details')->insert($finalInsertArray);
                                                } catch (QueryException $e) {   
                                                    Log::error('Error in YourScheduledTask: ' . $e->getMessage());
                                                    echo "Database insertion failed: " . $e->getMessage();
                                                    echo $e->getTraceAsString();
                                                    die;
                                                }
                                                
                                                unset($finalInsertArray, $orderDetailsArray, $excelInsertArray);
                                            }
        
                                            $count++; 
                                        }
                                    } else {
                                        continue;
                                    }
                                }
                            }

                            unset($workSheetArray1, $count, $maxNonEmptyValue);
                            if (isset($finalInsertArray) && !empty($finalInsertArray)) {
                                try {
                                    DB::table('uploaded_files')
                                    ->where('id', $fileValue->id)
                                    ->update([
                                        'cron' => 5
                                    ]);

                                    if ($fileValue->supplier_id == 1) {
                                        DB::table('g_and_t_laboratories_charles_river_order')->insert($excelInsertArray);
                                    } elseif ($fileValue->supplier_id == 2) {
                                        DB::table('grainger_order')->insert($excelInsertArray);
                                    } elseif ($fileValue->supplier_id == 3) {
                                        DB::table('office_depot_order')->insert($excelInsertArray);
                                    } elseif ($fileValue->supplier_id == 4) {   
                                        // DB::table('staples_order')->insert($excelInsertArray);
                                        DB::table('staples_orders_data')->insert($excelInsertArray);
                                    } elseif ($fileValue->supplier_id == 5) {
                                        DB::table('wb_mason_order')->insert($excelInsertArray);
                                    } elseif ($fileValue->supplier_id == 6) {
                                        DB::table('lyreco_order')->insert($excelInsertArray);
                                    } elseif ($fileValue->supplier_id == 7) {
                                        DB::table('odp_order')->insert($excelInsertArray);
                                    } else {

                                    }
                                    if ($fileValue->supplier_id != 7) {
                                        if (isset($orderDetailsArray) && !empty($orderDetailsArray)) {
                                            DB::table('order_details')->insert($orderDetailsArray);
                                        }
                                    }
                                    DB::table('order_product_details')->insert($finalInsertArray);
                                } catch (QueryException $e) {   
                                    Log::error('Error in YourScheduledTask: ' . $e->getMessage());
                                    echo "Database insertion failed: " . $e->getMessage();
                                }
                            }

                            unset($finalInsertArray, $finalOrderInsertArray, $excelInsertArray);
                        }
                    try {
                        /** Update the 'cron' field three after processing done */
                        DB::table('uploaded_files')->where('id', $fileValue->id)->update(['cron' => 6]);
    
                        $this->info('Uploaded files processed successfully.');
                    } catch (QueryException $e) {   
                        echo "Database updation failed: " . $e->getMessage();
                        die;
                    }
                } catch (\Exception $e) {
                    /** Update the 'cron' field three after processing done */
                    // DB::table('uploaded_files')->where('id', $fileValue->id)->update(['cron' => 1]);
                    echo "Error loading spreadsheet: " . $e->getMessage();
                }
            } else {
                echo "No file left to process.";
            }
        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
            echo "Error loading spreadsheet: " . $e->getMessage();
            die;
        } catch (QueryException $e) {   
            echo "Database table uploaded_files select query failed: " . $e->getMessage();
            die;
        }  
    }
}
