<?php

namespace App\Console\Commands;

use Illuminate\Support\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use App\Models\{Account, Order, OrderDetails, UploadedFiles};

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
            $fileValue = DB::table('uploaded_files')->select('id', 'supplier_id', 'file_name', 'start_date', 'end_date', 'created_by')->where('cron', '=', UploadedFiles::UPLOAD)->whereNull('deleted_by')->first();

            // $monthsDifference = $interval->m;
            // $yearsDifference = $interval->y;
            
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
                    if (in_array($value->id, [14, 44, 19, 199])) {
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

                    if (in_array($value->id, [1, 18, 48, 71, 125, 148, 203])) {
                        $columnArray[$value->supplier_id]['customer_number'] = $value->field_name;
                    }

                    if (in_array($value->id, [2, 19, 49, 72, 126, 149, 204])) {
                        $columnArray[$value->supplier_id]['customer_name'] = $value->field_name;
                    }

                    if ($value->supplier_id == 7) {
                        $columnArray[$value->supplier_id]['amount'] = '';
                    }

                    if (in_array($value->id, [12, 38, 65, 122, 143, 185])) {
                        $columnArray[$value->supplier_id]['amount'] = $value->field_name;
                    }

                    if (in_array($value->supplier_id, [1, 7])) {
                        $columnArray[$value->supplier_id]['invoice_no'] = '';
                    }

                    if (in_array($value->id, [43, 69, 101, 127, 194])) {
                        $columnArray[$value->supplier_id]['invoice_no'] = $value->field_name;
                    }

                    if (in_array($value->id, [24, 68, 103, 128, 195, 258])) {
                        $columnArray[$value->supplier_id]['invoice_date'] = $value->field_name;
                    }

                    if (in_array($value->supplier_id, [7])) {
                        $columnArray[$value->supplier_id]['invoice_date'] = '';
                    }

                    if ($value->supplier_id == 1) {
                        if ($value->id == 13) {
                            $offCoreSpend = $value->field_name;
                        }
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
                            $columnArray1['71'] => 'master_customer',
                            $columnArray1['72'] => 'master_name',
                            $columnArray1['73'] => 'bill_to_number',
                            $columnArray1['74'] => 'bill_to_name',
                            $columnArray1['75'] => 'ship_to_number',
                            $columnArray1['76'] => 'ship_to_name',
                            $columnArray1['77'] => 'ship_to_address_line1',
                            $columnArray1['78'] => 'ship_to_address_line2',
                            $columnArray1['79'] => 'ship_to_address_line3',
                            $columnArray1['80'] => 'ship_to_city',
                            $columnArray1['81'] => 'ship_to_state',
                            $columnArray1['82'] => 'ship_to_zipcode',
                            $columnArray1['83'] => 'last_ship_date',
                            $columnArray1['84'] => 'ship_to_create_date',
                            $columnArray1['85'] => 'ship_to_status',
                            $columnArray1['86'] => 'line_item_budget_center',
                            $columnArray1['87'] => 'cust_po_rel',
                            $columnArray1['88'] => 'cust_po',
                            $columnArray1['89'] => 'order_contact',
                            $columnArray1['90'] => 'order_contact_phone',
                            $columnArray1['91'] => 'ship_to_contact',
                            $columnArray1['92'] => 'order_number',
                            $columnArray1['93'] => 'order_date',
                            $columnArray1['94'] => 'shipped_date',
                            // $columnArray1['260'] => 
                            $columnArray1['95'] => 'trans_ship_to_line3',
                            $columnArray1['96'] => 'shipment_number',
                            $columnArray1['97'] => 'trans_type_code',
                            $columnArray1['98'] => 'order_method_desc',
                            $columnArray1['99'] => 'pymt_type',
                            $columnArray1['100'] => 'pymt_method_desc',
                            $columnArray1['101'] => 'invoice_number',
                            $columnArray1['102'] => 'summary_invoice_number',
                            $columnArray1['103'] => 'invoice_date',
                            $columnArray1['104'] => 'cvnce_card_flag',
                            $columnArray1['105'] => 'sku_number',
                            $columnArray1['106'] => 'item_description',
                            $columnArray1['107'] => 'staples_advantage_item_description',
                            $columnArray1['108'] => 'sell_uom',
                            $columnArray1['109'] => 'qty_in_sell_uom',
                            $columnArray1['110'] => 'staples_own_brand',
                            $columnArray1['111'] => 'diversity_cd',
                            $columnArray1['112'] => 'diversity',
                            $columnArray1['113'] => 'diversity_subtype_cd',
                            $columnArray1['114'] => 'diversity_subtype',
                            $columnArray1['115'] => 'contract_flag',
                            $columnArray1['116'] => 'sku_type',
                            $columnArray1['117'] => 'trans_source_sys_cd',
                            $columnArray1['118'] => 'transaction_source_system',
                            $columnArray1['119'] => 'item_frequency',
                            $columnArray1['120'] => 'number_orders_shipped',
                            $columnArray1['121'] => 'qty',
                            $columnArray1['122'] => 'adj_gross_sales',
                            $columnArray1['123'] => 'avg_sell_price',
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
                        // print_r($fileValue->created_by);die;
                        $reader = new Xlsx(); /** Creating object of php excel library class */ 

                        /** Loading excel file using path and name of file from table "uploaded_file" */
                        $spreadSheet = $reader->load($destinationPath . '/' . $fileValue->file_name, 2);
                        
                        $sheetCount = $spreadSheet->getSheetCount(); /** Getting sheet count for run loop on index */
                        
                        if ($fileValue->supplier_id == 4 || $fileValue->supplier_id == 3) {
                            $sheetCount = ($sheetCount > 1) ? $sheetCount - 2 : $sheetCount; /** Handle case if sheet count is one */
                        } else {
                            $sheetCount = ($sheetCount > 1) ? $sheetCount - 1 : $sheetCount;
                        }
                        
                        // print_r($sheetCount);
                        // die;

                        $supplierFilesNamesArray = [
                            1 => 'Usage By Location and Item',
                            2 => 'Invoice Detail Report',
                            // 3 => '',
                            4 => 'All Shipped Order Detail',
                            5 => 'Centerpoint_Summary_Report',
                            6 => 'Blad1',
                            7 => 'Weekly Sales Account Summary', 
                        ];

                        DB::table('uploaded_files')->where('id', $fileValue->id)
                        ->update([
                        'cron' => 4
                        ]);

                        // print_r($sheetCount);die;
                        for ($i = 0; $i <= $sheetCount; $i++) {
                            $count = $maxNonEmptyCount = 0;

                            // print_r($i);
                            
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

                            /** Clean up the values */
                            $maxNonEmptyValue = array_map(function ($value) {
                                /** Remove line breaks and trim whitespace */
                                return str_replace(["\r", "\n"], '', $value);
                            }, $maxNonEmptyValue);

                            // print_r($maxNonEmptyValue);
                            // die;

                            if ($fileValue->supplier_id == 7) {
                                $weeklyPriceColumnArray = [];
                                foreach ($maxNonEmptyValue as $key => $value) {
                                    if ($key >= 6) {
                                        $weeklyPriceColumnArray[$key] = $value;
                                        // $weeklyArrayKey++;
                                    }
                                }
                            }

                            // print_r($weeklyPriceColumnArray);
                            // die;

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
                                        // $gdPerent = Account::where('account_number', $row[$keyGrandParent])->first();
                                        // $perent = Account::where('account_number', $row[$keyParent])->first();
                                        $customer = Account::where('account_number', $row[$keyCustomer])->first();
                                        if (empty($customer)) {
                                            Account::create([
                                                'parent_id' => $row[$keyParent],
                                                'parent_name' => $row[$keyParentName],
                                                'created_by' => $fileValue->created_by,
                                                'account_number' => $row[$keyCustomer],
                                                'customer_name' => $row[$keyCustomerName],
                                                'grandparent_id' => $row[$keyGrandParent],
                                                'category_supplier' => $fileValue->supplier_id,
                                                'grandparent_name' => $row[$keyGrandParentName],
                                            ]);
                                        }

                                        // if (empty($gdPerent) && empty($perent) && empty($customer)) {
                                            // $lastInsertGdPerentId = Account::create(['category_supplier' => $fileValue->supplier_id, 'account_number' => $row[$keyGrandParent], 'alies' => $row[$keyGrandParentName], 'parent_id' => null, 'created_by' => $fileValue->created_by]);

                                            // $lastInsertPerentId = Account::create(['category_supplier' => $fileValue->supplier_id, 'account_number' => $row[$keyParent], 'alies' => $row[$keyParentName], 'parent_id' => $lastInsertGdPerentId->id, 'created_by' => $fileValue->created_by]);
                                            
                                            // Account::create(['category_supplier' => $fileValue->supplier_id, 'customer_number' => $row[$keyCustomer], 'alies' => $row[$keyCustomerName], 'parent_id' => $lastInsertPerentId->id, 'created_by' => $fileValue->created_by]);

                                        // } elseif (!empty($gdPerent) && empty($perent) && empty($customer)) {
                                        //     $lastInsertPerentId = Account::create(['category_supplier' => $fileValue->supplier_id, 'account_number' => $row[$keyParent], 'alies' => $row[$keyParentName], 'parent_id' => $gdPerent->id, 'created_by' => $fileValue->created_by]);

                                        //     Account::create(['category_supplier' => $fileValue->supplier_id, 'account_number' => $row[$keyCustomer], 'alies' => $row[$keyCustomerName], 'parent_id' => $lastInsertPerentId->id, 'created_by' => $fileValue->created_by]);

                                        // } elseif (!empty($gdPerent) && !empty($perent) && empty($customer)) {
                                        //     Account::create(['category_supplier' => $fileValue->supplier_id, 'account_number' => $row[$keyCustomer], 'alies' => $row[$keyCustomerName], 'parent_id' => $perent->id, 'created_by' => $fileValue->created_by]);

                                        // } else {
                                        //     // echo "hello";
                                        // }
                                    }

                                    if (in_array($fileValue->supplier_id, [1, 4, 5, 6])) {
                                        $customer = Account::where('account_number', $row[$keyCustomer])->first();
                                        if (empty($customer)) {
                                            if (empty($customer)) {
                                                Account::create([
                                                    // 'parent_id' => $row[$keyParent],
                                                    // 'parent_name' => $row[$keyParentName],
                                                    'created_by' => $fileValue->created_by,
                                                    'account_number' => $row[$keyCustomer],
                                                    'customer_name' => $row[$keyCustomerName],
                                                    // 'grandparent_id' => $row[$keyGrandParent],
                                                    'category_supplier' => $fileValue->supplier_id,
                                                    // 'grandparent_name' => $row[$keyGrandParentName],
                                                ]);
                                            }
                                            // Account::create(['category_supplier' => $fileValue->supplier_id, 'account_number' => $row[$keyCustomer], 'alies' => $row[$keyCustomerName], 'parent_id' => null, 'created_by' => $fileValue->created_by]);
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
                                            if ($fileValue->supplier_id == 1) {
                                                $keyOffCoreAmount = array_search($offCoreSpend, $maxNonEmptyValue);
                                            }
    
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
                                                        if ($columnArray2[$fileValue->supplier_id][trim($maxNonEmptyValue[$key1])] == 'invoice_date') {
                                                            $excelInsertArray[$key][$columnArray2[$fileValue->supplier_id][trim($maxNonEmptyValue[$key1])]] = date_format(date_create($row[$keyInvoiceDate]),'Y-m-d');
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
                                                            'supplier_id' => $fileValue->supplier_id,
                                                            'amount' => str_replace(",", "", number_format($row[$key], 2, '.')),
                                                            'date' =>  (!empty($keyInvoiceDate) && !empty($row[$keyInvoiceDate])) ? (($row[$keyInvoiceDate] && $fileValue->supplier_id == 4) ? (Carbon::createFromFormat('Y-m-d H:i:s', $row[$keyInvoiceDate])->format('Y-m-d H:i:s')) : (Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[$keyInvoiceDate]))->format('Y-m-d H:i:s'))) : ($fileValue->start_date),
                                                            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                            'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                            'customer_number' => (!empty($keyCustomerNumber) && !empty($row[$keyCustomerNumber])) ? ($row[$keyCustomerNumber]) : (''),
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
                                                        'amount' => (isset($keyAmount) && !empty($row[$keyAmount])) ? ($row[$keyAmount]) : ((!empty($keyOffCoreAmount) && !empty($row[$keyOffCoreAmount]) && $fileValue->supplier_id) ? ($row[$keyOffCoreAmount]) : ('0.0')),
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
                                                        'amount' => (isset($keyAmount) && !empty($row[$keyAmount])) ? ($row[$keyAmount]) : ((!empty($keyOffCoreAmount) && !empty($row[$keyOffCoreAmount]) && $fileValue->supplier_id) ? ($row[$keyOffCoreAmount]) : ('0.0')),
                                                        'date' =>  (isset($keyInvoiceDate) && !empty($row[$keyInvoiceDate])) ? (($row[$keyInvoiceDate] && $fileValue->supplier_id == 4) ? (Carbon::createFromFormat('Y-m-d H:i:s', $row[$keyInvoiceDate])->format('Y-m-d H:i:s')) : (Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[$keyInvoiceDate]))->format('Y-m-d H:i:s'))) : ($fileValue->start_date),
                                                        'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                        'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                        'customer_number' => $row[$keyCustomerNumber],
                                                    ]);
                                                }
    
                                                if ($weeklyCheck) {
                                                    $orderDetailsArray[] = [
                                                        'data_id' => $fileValue->id,
                                                        'order_id' => $orderLastInsertId->id,
                                                        'created_by' => $fileValue->created_by,
                                                        'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                        'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                                        'invoice_date' => (isset($keyInvoiceDate) && !empty($row[$keyInvoiceDate])) ? (($row[$keyInvoiceDate] && $fileValue->supplier_id == 4) ? (Carbon::createFromFormat('Y-m-d H:i:s', $row[$keyInvoiceDate])->format('Y-m-d H:i:s')) : (Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[$keyInvoiceDate]))->format('Y-m-d H:i:s'))) : ($fileValue->start_date),
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
                                                        'invoice_date' => (isset($keyInvoiceDate) && !empty($row[$keyInvoiceDate])) ? (($row[$keyInvoiceDate] && $fileValue->supplier_id == 4) ? (Carbon::createFromFormat('Y-m-d H:i:s', $row[$keyInvoiceDate])->format('Y-m-d H:i:s')) : (Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($row[$keyInvoiceDate]))->format('Y-m-d H:i:s'))) : ($fileValue->start_date),
                                                        'order_file_name' => $fileValue->supplier_id."_monthly_".date_format(date_create($fileValue->start_date),"Y/m"),
                                                    ];
                                                }
                                            }
    
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
                                                        DB::table('staples_order')->insert($excelInsertArray);
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
                                    DB::table('uploaded_files')->where('id', $fileValue->id)
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
                                        DB::table('staples_order')->insert($excelInsertArray);
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
                } catch (\Exception $e) {
                    echo "Error loading spreadsheet: " . $e->getMessage();
                }

                try {
                    /** Update the 'cron' field three after processing done */
                    DB::table('uploaded_files')->where('id', $fileValue->id)->update(['cron' => 6]);

                    $this->info('Uploaded files processed successfully.');
                } catch (QueryException $e) {   
                    echo "Database updation failed: " . $e->getMessage();
                    die;
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
