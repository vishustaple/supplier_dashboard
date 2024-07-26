<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class Order extends Model
{
    use HasFactory;
    protected $table = 'orders';
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'date',
        'amount',
        'data_id',
        'created_by',
        'supplier_id',
        'customer_number',
    ];

    public function orderProductDetail() {
        return $this->hasMany(ExcelData::class);
    }

    public static function getFilterdData($filter = [], $csv=false){
        $year = $filter['year'];
        $res[1] =['January', 'February', 'March'];
        $res[2] = ['April', 'May', 'June'];
        $res[3] = ['July', 'August', 'September'];
        $res[4] = ['October', 'November', 'December'];
        $monthDates = [];

        for ($month = 1; $month <= 12; $month++) {
            $start = date('Y-m-01', strtotime("$year-$month-01"));
            $end = date('Y-m-t', strtotime("$year-$month-01"));
            $monthDates[] = ['start_date' => $start, 'end_date' => $end];
        }

        $startDate1 = $monthDates[0]['start_date'];
        $endDate1 = $monthDates[2]['end_date'];
        
        $startDate2 = $monthDates[3]['start_date'];
        $endDate2 = $monthDates[5]['end_date'];
        
        $startDate3 = $monthDates[6]['start_date'];
        $endDate3 = $monthDates[8]['end_date'];
        
        $startDate4 = $monthDates[9]['start_date'];
        $endDate4 = $monthDates[11]['end_date'];

        $query = DB::table('master_account_detail')->select('master_account_detail.account_number as account_number', 'master_account_detail.category_supplier as supplier');

        if (isset($filter['supplier']) && !empty($filter['supplier'])) {
            $query->whereIn('master_account_detail.category_supplier', $filter['supplier']);
        } else {
            if ($csv == true) {
                $finalArray['heading'] = [
                    'Sku',
                    'Description',
                    'Uom',
                    'Category',
                    'Quantity Purchased',
                    'Total Spend',
                    'Unit Q1 Price',
                    'Unit Q2 Price',
                    'Unit Q3 Price',
                    'Unit Q4 Price',
                    'Web Q1 Price',
                    'Web Q2 Price',
                    'Web Q3 Price',
                    'Web Q4 Price',
                    'Lowest Price',
                ];
                return $finalArray;
            } else {
                return [
                    'data' => [],
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                ];
            }
        }

        if (isset($filter['account_name']) && !empty($filter['account_name'])) {
            $query->where('account_name', $filter['account_name']);
        } else {
            if ($csv == true) {
                $finalArray['heading'] = [
                    'Sku',
                    'Description',
                    'Uom',
                    'Category',
                    'Quantity Purchased',
                    'Total Spend',
                    'Unit Q1 Price',
                    'Unit Q2 Price',
                    'Unit Q3 Price',
                    'Unit Q4 Price',
                    'Web Q1 Price',
                    'Web Q2 Price',
                    'Web Q3 Price',
                    'Web Q4 Price',
                    'Lowest Price',
                ];
                return $finalArray;
            } else {
                return [
                    'data' => [],
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                ];
            }
        }

        $accountNumber = [];
        foreach ($query->get() as $value) {
            $accountNumber[] = $value->account_number;
        }

        if (in_array(3, $filter['supplier'])) {
            $query = DB::table('office_depot_order')
            ->selectRaw(
                'sku,
                uom,
                SUM(qty_shipped) as quantity_purchased,
                product_description as description,
                SUM(total_spend) as total_spend,
                MAX(CASE WHEN `office_depot_order`.`shipped_date` BETWEEN ? AND ? THEN `office_depot_order`.`unit_net_price` ELSE 0 END) AS unit_price_q1_price,
                MAX(CASE WHEN `office_depot_order`.`shipped_date` BETWEEN ? AND ? THEN `office_depot_order`.`unit_net_price` ELSE 0 END) AS unit_price_q2_price,
                MAX(CASE WHEN `office_depot_order`.`shipped_date` BETWEEN ? AND ? THEN `office_depot_order`.`unit_net_price` ELSE 0 END) AS unit_price_q3_price,
                MAX(CASE WHEN `office_depot_order`.`shipped_date` BETWEEN ? AND ? THEN `office_depot_order`.`unit_net_price` ELSE 0 END) AS unit_price_q4_price,
                MAX(CASE WHEN `office_depot_order`.`shipped_date` BETWEEN ? AND ? THEN `office_depot_order`.`unit_web_price` ELSE 0 END) AS web_price_q1_price,
                MAX(CASE WHEN `office_depot_order`.`shipped_date` BETWEEN ? AND ? THEN `office_depot_order`.`unit_web_price` ELSE 0 END) AS web_price_q2_price,
                MAX(CASE WHEN `office_depot_order`.`shipped_date` BETWEEN ? AND ? THEN `office_depot_order`.`unit_web_price` ELSE 0 END) AS web_price_q3_price,
                MAX(CASE WHEN `office_depot_order`.`shipped_date` BETWEEN ? AND ? THEN `office_depot_order`.`unit_web_price` ELSE 0 END) AS web_price_q4_price',
                [
                    $startDate1,
                    $endDate1,
                    $startDate2,
                    $endDate2,
                    $startDate3,
                    $endDate3,
                    $startDate4,
                    $endDate4,
                    $startDate1,
                    $endDate1,
                    $startDate2,
                    $endDate2,
                    $startDate3,
                    $endDate3,
                    $startDate4,
                    $endDate4
                ]
            )
            ->groupBy('sku');
            $query->whereIn('customer_id', $accountNumber);

            if (isset($filter['year'])) {
                $query->whereYear('shipped_date', $filter['year']);
            }
            
            if ($filter['core'] == 1) {
                $query->where('core_flag', 'N');
                $query->whereNot('dept', 'Product Assembly');
            } else {
                $query->where(function($query) {
                    $query->orWhere('core_flag', 'Y')
                        ->orWhere('dept', 'Product Assembly');
                });
            }

            $query->orderBy('total_spend', 'desc')->limit(100);
            $queryData1 = $query->get()->toArray();
        } else {
            $queryData1 = [];
        }

        if (in_array(4, $filter['supplier'])) {
            $query = DB::table('staples_order')
            ->selectRaw(
                'sku_id AS sku,
                sell_uom_id AS uom,
                SUM(qty) AS quantity_purchased,
                item_description_id AS description,
                primary_product_hierarchy_desc AS category,
                SUM(adj_gross_sales) as total_spend'
            )
            ->groupBy('sku_id');
            $query->whereIn('master_customer_number_id', $accountNumber);

            if (isset($filter['year'])) {
                $query->whereYear('shipped_date_id', $filter['year']);
            }

            if ($filter['core'] == 1) {
                $query->whereNotIn('transaction_source_system_desc', ['Staples Promotional Products USA', 'Staples Technology Solutions'])
                    ->where('on_contract_id', 'N')
                    ->whereNotIn('primary_product_hierarchy_desc', ['STS Technology', 'Promo', 'Transactional Technology', 'Transactional Furniture', 'Install Labor']);
            } else {
                $query->where(function($query) {
                    $query->orWhereIn('transaction_source_system_desc', ['Staples Promotional Products USA', 'Staples Technology Solutions'])
                        ->orWhere('on_contract_id', 'Y')
                        ->orWhereIn('primary_product_hierarchy_desc', ['STS Technology', 'Promo', 'Transactional Technology', 'Transactional Furniture', 'Install Labor']);
                });
            }

            $query->orderBy('total_spend', 'desc')->limit(100);
            $queryData2 = $query->get()->toArray();
            // dd($query->toSql(), $query->getBindings());

            $query = DB::table('staples_orders_data')
            ->selectRaw(
               'sku AS sku,
                sell_uom AS uom,
                SUM(qty) AS quantity_purchased,
                item_description AS description,
                primary_product_hierarchy AS category,
                SUM(adj_gross_sales) as total_spend'
            )
            ->groupBy('sku');
            $query->whereIn('master_customer_number', $accountNumber);

            if (isset($filter['year'])) {
                $query->whereYear('shipped_date', $filter['year']);
            }

            if ($filter['core'] == 1) {
                $query->whereNotIn('transaction_source_system', ['Staples Promotional Products USA', 'Staples Technology Solutions'])
                    ->where('on_contract', 'N')
                    ->whereNotIn('primary_product_hierarchy', ['STS Technology', 'Promo', 'Transactional Technology', 'Transactional Furniture', 'Install Labor']);
            } else {
                $query->where(function($query) {
                    $query->orWhereIn('transaction_source_system', ['Staples Promotional Products USA', 'Staples Technology Solutions'])
                        ->orWhere('on_contract', 'Y')
                        ->orWhereIn('primary_product_hierarchy', ['STS Technology', 'Promo', 'Transactional Technology', 'Transactional Furniture', 'Install Labor']);
                });
            }

            $query->orderBy('total_spend', 'desc')->limit(100);
            $queryData6 = $query->get()->toArray();
        } else {
            $queryData2 = [];
            $queryData6 = [];
        }

        if (in_array(5, $filter['supplier'])) {
            $query = DB::table('wb_mason_order')
            ->selectRaw(
                'category_umbrella AS category,
                uo_m,
                SUM(qty) AS quantity_purchased,
                item_num AS sku,
                item_name AS description,
                SUM(ext_price) as total_spend'
            )
            ->groupBy('item_num');
            $query->whereIn('customer_num', $accountNumber);

            if (isset($filter['year'])) {
                $query->whereYear('invoice_date', $filter['year']);
            }

            if ($filter['core'] == 2) {
                $query->where(function($query) {
                    $query->orWhere('price_method', 'LIKE', 'PPL%')
                        ->orWhere('price_method', 'LIKE', 'CTL%');
                });
            } else {
                $query->where(function($query) {
                    $query->where('price_method', 'NOT LIKE', 'PPL%')
                        ->where('price_method', 'NOT LIKE', 'CTL%');
                });
            } 

            $query->orderBy('total_spend', 'desc')->limit(100);
            $queryData3 = $query->get()->toArray();
        } else {
            $queryData3 = [];
        }

        if (in_array(2, $filter['supplier'])) {
            $query = DB::table('grainger_order')
            ->selectRaw(
                'material  AS sku,
                material_description AS description,
                material_segment AS category,
                SUM(billing_qty) AS quantity_purchased,
                SUM(purchase_amount) as total_spend'
            )
            ->groupBy('material');
            $query->whereIn('account_number', $accountNumber);

            if (isset($filter['year'])) {
                $query->whereYear('bill_date', $filter['year']);
            }

            if ($filter['core'] == 2) {
                $query->where('active_price_point', 'CSP');
            } else {
                $query->whereNotIn('active_price_point', ['CSP']);
            }

            $query->orderBy('total_spend', 'desc')->limit(100);
            $queryData4 = $query->get()->toArray();
        } else {
            $queryData4 = [];
        }

        if (in_array(1, $filter['supplier'])) {
            $query = DB::table('g_and_t_laboratories_charles_river_order')
            ->selectRaw(
                'product  AS sku,
                description AS description,
                categories AS category,
                SUM(quantity_shipped) AS quantity_purchased,
                SUM(total_spend) as total_spend'
            )
            ->groupBy('product');
            $query->whereIn('sold_to_account', $accountNumber);

            if (isset($filter['year'])) {
                $query->whereYear('date', $filter['year']);
            }

            if ($filter['core'] == 2) {
                $query->where('on_core_spend', '>', 0);
            } else {
                $query->where('off_core_spend', '>', 0);
            }

            $query->orderBy('total_spend', 'desc')
            ->limit(100);

            $queryData5 = $query->get()->toArray();
        } else {
            $queryData5 = [];
        }

        $queryData = array_merge($queryData1, $queryData2, $queryData3, $queryData4, $queryData5, $queryData6);

        /** Function to sort the array based on "total_spend" */
        usort($queryData, function($a, $b) {
            return $b->total_spend - $a->total_spend; /** Sort in descending order */
        });

        /** Select the top 100 elements */
        $queryData = array_slice($queryData, 0, 100);

        $newFinalArray = [];
        foreach ($queryData as $value) {
            if (isset($value->unit_price_q1_price)) {
                $prices = [
                    $value->unit_price_q1_price,
                    $value->unit_price_q2_price,
                    $value->unit_price_q3_price,
                    $value->unit_price_q4_price,
                    $value->web_price_q1_price,
                    $value->web_price_q2_price,
                    $value->web_price_q3_price,
                    $value->web_price_q4_price,
                ];
                
                /** Remove zero values */
                $prices = array_filter($prices, function($price) {
                    return $price > 0;
                });
                
                if ($prices) {
                    /** Find the lowest non-zero price */
                    $lowestPrice = min($prices);
                } else {
                    $lowestPrice = 0;
                }
            } else {
                $lowestPrice = 0;
            }
        
            if (!in_array(3, $filter['supplier'])) {
                $newFinalArray[] = [
                    'sku' => ((isset($value->sku)) ? ($value->sku) : ('')),
                    'description' => ((isset($value->description)) ? ($value->description) : ('')),
                    'uom' => ((isset($value->uom) && !in_array($filter['supplier'], [2])) ? ($value->uom) : ('')),
                    'category' => ((isset($value->category)) ? ($value->category) : ('')),
                    'quantity_purchased' => ((isset($value->quantity_purchased)) ? ($value->quantity_purchased) : ('')),
                    'total_spend' => (($csv) ? ($value->total_spend) : ('$'.number_format($value->total_spend, 2))),
                    'unit_price_q1_price' => '',
                    'unit_price_q2_price' => '',
                    'unit_price_q3_price' => '',
                    'unit_price_q4_price' => '',
                    'web_price_q1_price' => '',
                    'web_price_q2_price' => '',
                    'web_price_q3_price' => '',
                    'web_price_q4_price' => '',
                    'lowest_price' => '',
                ];
            } else {
                $newFinalArray[] = [
                    'sku' => ((isset($value->sku)) ? ($value->sku) : ('')),
                    'description' => ((isset($value->description)) ? ($value->description) : ('')),
                    'uom' => ((isset($value->uom) && !in_array($filter['supplier'], [2])) ? ($value->uom) : ('')),
                    'category' => ((isset($value->category)) ? ($value->category) : ('')),
                    'quantity_purchased' => ((isset($value->quantity_purchased)) ? ($value->quantity_purchased) : ('')),
                    'total_spend' => (($csv) ? ($value->total_spend) : ('$'.number_format($value->total_spend, 2))),
                    'unit_price_q1_price' => (($csv) ? (((isset($value->unit_price_q1_price)) ? ($value->unit_price_q1_price) : ('0'))) : ('$'.number_format(((isset($value->unit_price_q1_price)) ? ($value->unit_price_q1_price) : (0)), 2))),
                    'unit_price_q2_price' =>  (($csv) ? (((isset($value->unit_price_q2_price)) ? ($value->unit_price_q2_price) : ('0'))) : ('$'.number_format(((isset($value->unit_price_q2_price)) ? ($value->unit_price_q2_price) : (0)), 2))),
                    'unit_price_q3_price' =>  (($csv) ? (((isset($value->unit_price_q3_price)) ? ($value->unit_price_q3_price) : ('0'))) : ('$'.number_format(((isset($value->unit_price_q3_price)) ? ($value->unit_price_q3_price) : (0)), 2))),
                    'unit_price_q4_price' =>  (($csv) ? (((isset($value->unit_price_q4_price)) ? ($value->unit_price_q4_price) : ('0'))) : ('$'.number_format(((isset($value->unit_price_q4_price)) ? ($value->unit_price_q4_price) : (0)), 2))),
                    'web_price_q1_price' => (($csv) ? (((isset($value->web_price_q1_price)) ? ($value->web_price_q1_price) : ('0'))) : ('$'.number_format(((isset($value->web_price_q1_price)) ? ($value->web_price_q1_price) : (0)), 2))),
                    'web_price_q2_price' => (($csv) ? (((isset($value->web_price_q2_price)) ? ($value->web_price_q2_price) : ('0'))) : ('$'.number_format(((isset($value->web_price_q2_price)) ? ($value->web_price_q2_price) : (0)), 2))),
                    'web_price_q3_price' => (($csv) ? (((isset($value->web_price_q3_price)) ? ($value->web_price_q3_price) : ('0'))) : ('$'.number_format(((isset($value->web_price_q3_price)) ? ($value->web_price_q3_price) : (0)), 2))),
                    'web_price_q4_price' => (($csv) ? (((isset($value->web_price_q4_price)) ? ($value->web_price_q4_price) : ('0'))) : ('$'.number_format(((isset($value->web_price_q4_price)) ? ($value->web_price_q4_price) : (0)), 2))),
                    'lowest_price' => (($csv) ? ($lowestPrice) : ('$'.number_format($lowestPrice, 2))),
                ];
            }
        }

        if ($csv == true) {
                $newFinalArray['heading'] = [
                    'Sku',
                    'Description',
                    'Uom',
                    'Category',
                    'Quantity Purchased',
                    'Total Spend',
                    'Unit Q1 Price',
                    'Unit Q2 Price',
                    'Unit Q3 Price',
                    'Unit Q4 Price',
                    'Web Q1 Price',
                    'Web Q2 Price',
                    'Web Q3 Price',
                    'Web Q4 Price',
                    'Lowest Price',
                ];
            return $newFinalArray;
        } else {
            return [
                'data' => $newFinalArray,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
            ];
        }
    }

    public static function getSupplierReportFilterdData($filter = [], $csv = false){
        $orderColumnArray = [
            0 => 'suppliers.supplier_name',
            1 => 'm2.account_name',
            2 => 'amount',
        ];

        if (isset($filter['rebate_check']) && $filter['rebate_check'] == 1) {
            $orderColumnArray[3] = 'volume_rebate';
        } else {
            $orderColumnArray[3] = 'incentive_rebate';
        }

        if (isset($filter['rebate_check']) && $filter['rebate_check'] == 1) {
            $query = self::query()->selectRaw(
                "SUM(`orders`.`amount`) AS `amount`, 
                `m2`.`account_name` AS `account_name`,
                ((SUM(`orders`.`amount`)) / 100) * MAX(`rebate`.`volume_rebate`) AS `volume_rebate`,
                `rebate`.`volume_rebate` AS `volume_rebates`,
                `suppliers`.`supplier_name` AS `supplier_name`, 
                `orders`.`date` AS `date`"
            );
        } else {
            $query = self::query()->selectRaw(
                "SUM(`orders`.`amount`) AS `amount`, 
                `m2`.`account_name` AS `account_name`,
                ((SUM(`orders`.`amount`)) / 100) * MAX(`rebate`.`incentive_rebate`) AS `incentive_rebate`,
                `rebate`.`incentive_rebate` AS `incentive_rebates`,
                `suppliers`.`supplier_name` AS `supplier_name`, 
                `orders`.`date` AS `date`"
            );
        }

        $query->leftJoin('master_account_detail as m2', 'orders.customer_number', '=', 'm2.account_number')
        ->leftJoin('rebate', function($join) {
            $join->on('m2.account_name', '=', 'rebate.account_name')
            ->on('m2.category_supplier', '=', 'rebate.supplier');
        })

        ->leftJoin('suppliers', 'suppliers.id', '=', 'orders.supplier_id');
        
        if (isset($filter['supplier']) && $filter['supplier'] == 4){
            $query->leftJoin('order_product_details', 'order_product_details.order_id', '=', 'orders.id');
        }
        
        if (isset($filter['supplier']) && !empty($filter['supplier'])) {
            if ($filter['supplier'] == 3) {   
                if ($filter['rebate_check'] == 2) {
                    $query->leftJoin('order_product_details', 'order_product_details.order_id', '=', 'orders.id');
                    $query->whereIn('m2.grandparent_id', ["1637", "1718", "2140"]);
                    $query->where('order_product_details.key', 'DEPT');
                    // $query->whereNotIn('order_product_details.value', ['NON CODE', 'IMPULSE BUYS', 'MANAGE PRINT SERVICE', 'CUSTOM BUS  ESSTLS', 'CUSTOM OUTSOURC PRNT', 'PRODUCT ASSEMBLY', 'MARKETNG/VISUAL SRVC', 'OD ADVERT. GIVEAWAYS']);
                    $query->whereNotIn(
                        'order_product_details.value',
                        [
                            'NON CODE',
                            'IMPULSE BUYS',
                            'MANAGE PRINT SERVICE',
                            'custom bus essentials',
                            'CUSTOM OUTSOURC PRNT',
                            'PRODUCT ASSEMBLY',
                            'MARKETNG/VISUAL SRVC',
                            'OD ADVERT. GIVEAWAYS'
                        ]
                    );
                }
            } 
            $query->where('orders.supplier_id', $filter['supplier']);

            /** Year and quarter filter here */
            if (isset($filter['end_date']) && isset($filter['start_date'])) {
                $query->whereBetween('orders.date', [$filter['start_date'], $filter['end_date']]);
            }

            if ($filter['supplier'] == 4) {
                $query->whereRaw("
                    (
                        CASE 
                            WHEN order_product_details.key = 'Transaction Source System DESC' 
                                THEN 
                                    CASE 
                                        WHEN order_product_details.value NOT IN ('Staples Technology Solutions', 'Staples Promotional Products USA') THEN 1 
                                        ELSE 0 
                                    END
                            WHEN order_product_details.key = 'Transaction Source System'
                                THEN 
                                    CASE 
                                        WHEN order_product_details.value NOT IN ('Staples Technology Solutions', 'Staples Promotional Products USA') THEN 1 
                                        ELSE 0 
                                    END
                            ELSE 0
                        END
                    ) = 1
                ");
               
                // $query->whereIn('order_product_details.key', ['Transaction Source System DESC']);
                // $query->whereNotIn('order_product_details.value', ['Staples Technology Solutions', 'Staples Promotional Products USA']);
            }
        } else {
            if ($csv) {
                $finalArray['heading'] = [
                'Supplier',
                'Account_name',
                'Amount',
                'Volume Rebate',
                'Incentive Rebate',
                '',
                '',
                '',
                'Total Amount',
                'Total Volume Rebate',
                'Total Incentive Rebate',
                'Start Date',
                'End Date',
            ];

            return $finalArray;
            } else {
                return [
                    'data' => [],
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                ];
            }
        }

        
        /** Group by with account name */
        $query->groupBy('m2.account_name');
        $totalRecords = $query->getQuery()->getCountForPagination();

        /** Get total records count (without filtering) */
        if (isset($filter['order'][0]['column']) && isset($orderColumnArray[$filter['order'][0]['column']]) && isset($filter['order'][0]['dir'])) {
            /** Order by column and direction */
            $query->orderBy($orderColumnArray[$filter['order'][0]['column']], $filter['order'][0]['dir']);
        } else {
            $query->orderBy($orderColumnArray[0], 'asc');
        }

        /** Calculating total volume rebate, total incentive rebate and total amount */
        $totalAmount = $totalVolumeRebate = $totalIncentiveRebate = 0;
        foreach ($query->get() as $key => $value) {
            $totalVolumeRebate += $value->volume_rebate;
            $totalIncentiveRebate += $value->incentive_rebate;
            $totalAmount += $value->amount;
        }

        /** For debug query */
        // dd($query->toSql(), $query->getBindings());

        /** Formatting this */
        $totalAmounts = number_format($totalAmount, 2, '.', false);
        $totalVolumeRebates = number_format($totalVolumeRebate, 2, '.', false);
        $totalIncentiveRebates = number_format($totalIncentiveRebate, 2, '.', false);

        $totalAmount = number_format($totalAmount, 2);
        $totalVolumeRebate = number_format($totalVolumeRebate, 2);
        $totalIncentiveRebate = number_format($totalIncentiveRebate, 2);

        // dd($query->limit(100)->get());

        $formatuserdata = $query->when(isset($filter['start']) && isset($filter['length']), function ($query) use ($filter) {
            return $query->skip($filter['start'])->take($filter['length']);
        })->get();

        /** Making final array */
        $finalArray=[];
        if (isset($formatuserdata) && !empty($formatuserdata)) {
            foreach ($formatuserdata as $key => $value) {
                if ($csv) {
                    $finalArray[$key]['supplier'] = $value->supplier_name;
                    $finalArray[$key]['account_name'] = $value->account_name;
                    $finalArray[$key]['amount'] = number_format($value->amount, 2, '.', false);
                    if ($filter['rebate_check'] == 1) {
                        $finalArray[$key]['volume_rebate'] = number_format($value->volume_rebate, 2, '.', false);
                    }
                    if ($filter['supplier'] == 3 && $filter['rebate_check'] == 2) {
                        $finalArray[$key]['incentive_rebate'] = number_format($value->incentive_rebate, 2, '.', false);
                    }
                } else {
                    $finalArray[$key]['supplier'] = $value->supplier_name;
                    $finalArray[$key]['account_name'] = $value->account_name;
                    $finalArray[$key]['amount'] = '<input type="hidden" value="'.$totalAmount.'"class="total_amount"> $'.number_format($value->amount, 2);
                    $finalArray[$key]['volume_rebate'] = '<input type="hidden" value="'.$totalVolumeRebate.'"class="input_volume_rebate"> $'.number_format($value->volume_rebate, 2).' ('.(!empty($value->volume_rebates) ? ($value->volume_rebates.'%') : ('N/A')).')';
                    $finalArray[$key]['incentive_rebate'] = '<input type="hidden" value="'.$totalIncentiveRebate.'" class="input_incentive_rebate"> $'.number_format($value->incentive_rebate, 2).' ('.(!empty($value->incentive_rebates) ? ($value->incentive_rebates.'%') : ('N/A')).')';
                }
            }
        }
    
        if ($csv) {
            $startDates = date_format(date_create(trim($filter['start_date'])), 'm-d-Y');
            $endDates = date_format(date_create(trim($filter['end_date'])), 'm-d-Y');

            if ($filter['supplier'] == 3 && $filter['rebate_check'] == 2) {
                /** Defining heading array for csv genration */
                $finalArray['heading'] = [
                    'Supplier',
                    'Account_name',
                    'Amount',
                    'Incentive Rebate',
                    '',
                    '',
                    '',
                    'Total Amount',
                    $totalAmounts,
                    'Total Incentive Rebate',
                    $totalIncentiveRebates,
                    'Start Date',
                    $startDates,
                    'End Date',
                    $endDates
                ];
            } else {
                /** Defining heading array for csv genration */
                $finalArray['heading'] = [
                    'Supplier',
                    'Account_name',
                    'Amount',
                    'Volume Rebate',
                    '',
                    '',
                    '',
                    'Total Amount',
                    $totalAmounts,
                    'Total Volume Rebate',
                    $totalVolumeRebates,
                    'Total Incentive Rebate',
                    $totalIncentiveRebates,
                    'Start Date',
                    $startDates,
                    'End Date',
                    $endDates
                ];
            }

            return $finalArray;
        } else {
            /** Defining final array for datatable */
            return [
                'data' => $finalArray,
                'recordsTotal' => $totalRecords, // Use count of formatted data for total records
                'recordsFiltered' => $totalRecords, // Use total records from the query
            ];
        }
    }

    public static function getCommissionReportFilterdData($filter = [], $csv = false){
        $orderColumnArray = [
            0 => 'approved',
            1 => 'paid',
            2 => 'spend',
            3 => 'volume_rebate',
            4 => 'commission',
            5 => 'start_date',
            6 => 'end_date',
        ];

        $salesRep = SalesTeam::select(DB::raw('CONCAT(sales_team.first_name, " ", sales_team.last_name) as sales_rep'))
        ->where('id', $filter['sales_rep'])
        ->first();

        $query = CommissionRebate::query()->selectRaw(
            "GROUP_CONCAT(CONCAT_WS('_', `id`)) as `ids`,
            SUM(`commission`) as `commission`,
            SUM(`volume_rebate`) as `volume_rebate`,
            SUM(`spend`) as `spend`,
            `approved`,
            `paid`"
        );

        $query->groupBy('quarter');

        if (isset($filter['sales_rep']) && !empty($filter['sales_rep'])) {
            $query->where('sales_rep', $filter['sales_rep']);
        } else {
            return ['data' => [], 'recordsTotal' => 0, 'recordsFiltered' => 0];
        }
    
        if (isset($filter['approved']) || !empty($filter['approved'])) {
            $query->where('approved', $filter['approved']);
        } else {
            $query->whereIn('approved', [0, 1]);
        }

        if (isset($filter['paid']) || !empty($filter['paid'])) {
            $query->where('paid', $filter['paid']);
        } else {
            $query->whereIn('paid', [0, 1]);
        }

        /** Year and quarter filter here */
        if (isset($filter['year']) || !empty($filter['quarter'])) {
            $year = $filter['year'];
            $res[1] =['January', 'February', 'March'];
            $res[2] = ['April', 'May', 'June'];
            $res[3] = ['July', 'August', 'September'];
            $res[4] = ['October', 'November', 'December'];
            $monthDates = [];

            for ($month = 1; $month <= 12; $month++) {
                $start = date('Y-m-01', strtotime("$year-$month-01"));
                $end = date('Y-m-t', strtotime("$year-$month-01"));
                $monthDates[] = ['start_date' => $start, 'end_date' => $end];
            }

            if($filter['quarter'] == 'Quarter 1'){
                $startDate = $monthDates[0]['start_date'];
                $endDate = $monthDates[2]['end_date'];
            }

            if($filter['quarter'] == 'Quarter 2'){
                $startDate = $monthDates[3]['start_date'];
                $endDate = $monthDates[5]['end_date'];
            }

            if($filter['quarter'] == 'Quarter 3'){
                $startDate = $monthDates[6]['start_date'];
                $endDate = $monthDates[8]['end_date'];
            }

            if($filter['quarter'] == 'Quarter 4'){
                $startDate = $monthDates[9]['start_date'];
                $endDate = $monthDates[11]['end_date'];
            }

            if ($filter['quarter'] == 'Annual'){
                $startDate = $monthDates[0]['start_date'];
                $endDate = $monthDates[11]['end_date'];
                $query->where('spend', '!=', 0);    
            }

            // dd($startDate, $endDate);
            // $query->whereBetween($startDate, $endDate);
            $query->whereDate('start_date', '>=', $startDate)
                ->whereDate('end_date', '<=', $endDate);
        }

        // dd($query->toSql(), $query->getBindings());
        $totalRecords = $query->getQuery()->getCountForPagination();

        /** Get total records count (without filtering) */
        if (isset($filter['order'][0]['column']) && isset($orderColumnArray[$filter['order'][0]['column']]) && isset($filter['order'][0]['dir'])) {
            /** Order by column and direction */
            $query->orderBy($orderColumnArray[$filter['order'][0]['column']], $filter['order'][0]['dir']);
        } else {
            $query->orderBy($orderColumnArray[0], 'asc');
        }

        $datas = $query->get();
        // dd($datas);
        $annual = [];
        if (isset($datas) && $datas->isNotEmpty()) {
            if ($filter['quarter'] == 'Annual') {
                $annual["commission"] = 0;
                $annual["volume_rebate"] = 0;
                $annual["spend"] = 0;
                $annual["approved"] = 1;
                $annual["paid"] = 1;
                foreach ($datas as $key => $data) {
                    if (!isset($annual["ids"])) {
                        $annual["ids"] = $data->ids;
                    } else {
                        $annual["ids"] .= ($annual["ids"] === "" ? "" : ",") . $data->ids;
                    }
                    
                    $annual["commission"] += $data->commission;
                    $annual["volume_rebate"] += $data->volume_rebate;
                    $annual["spend"] += $data->spend;
                    $annual["paid"] *= $data->paid;
                    $annual["approved"] *= $data->approved;
                }
            }

            // dd($annual);
            /** Making final array */
            $finalArray=[];
            $ids = 1;
            foreach ($datas as $key => $data) {
                if ($csv) {
                    $finalArray[$key]['approved'] = ($data->approved == 1) ? ('Yes') : ('No');
                    $finalArray[$key]['paid'] = ($data->paid == 1) ? ('Yes') : ('No');
                    $finalArray[$key]['sales_rep'] = $salesRep->sales_rep;
                    $finalArray[$key]['amount'] = number_format($data->spend, 2, '.', false);
                    $finalArray[$key]['volume_rebate'] = number_format($data->volume_rebate, 2, '.', false);
                    $finalArray[$key]['commissions'] = number_format($data->commission, 2, '.', false);
                } else {
                    if ($data->approved == 1) {
                        $finalArray[$key]['approved'] = '<select data-approved_ids="'.$ids.'" data-approved_id="['.$data->ids.']" name="approved" class="form-control approved_input_select approved_'.$ids.'" '.(($data->paid == 1) ? ('disabled') : ('')).'> 
                            <option value="">--Select--</option>
                            <option value="1" selected>Yes</option>
                            <option value="0">NO</option>
                        </select>';
                    } else {
                        $finalArray[$key]['approved'] = '<select data-approved_ids="'.$ids.'" data-approved_id="['.$data->ids.']" name="approved" class="form-control approved_input_select approved_'.$ids.'" > 
                            <option value="" selected>--Select--</option>
                            <option value="1">Yes</option>
                            <option selected value="0">NO</option>
                        </select>';
                    }
    
                    if ($data->paid == 1) {
                        $finalArray[$key]['paid'] = '<select data-paid_ids="'.$ids.'" data-paid_id="['.$data->ids.']" name="paid" class="form-control paid_input_select paid_'.$ids.'" disabled> 
                            <option value="">--Select--</option>
                            <option value="1" selected>Yes</option>
                            <option value="0">NO</option>
                        </select>';
                    } else {
                        $finalArray[$key]['paid'] = '<select data-paid_ids="'.$ids.'" data-paid_id="['.$data->ids.']" name="paid" class="form-control paid_input_select paid_'.$ids.'" '.(($data->approved == 0) ? ('disabled') : ('')).'> 
                            <option value="">--Select--</option>
                            <option value="1">Yes</option>
                            <option value="0" selected>NO</option>
                        </select>';
                    }

                    $finalArray[$key]['end_date'] = date_format(date_create($endDate), 'm/d/Y');
                    $finalArray[$key]['start_date'] = date_format(date_create($startDate), 'm/d/Y');
                    $finalArray[$key]['sales_rep'] = $salesRep->sales_rep;
                    $finalArray[$key]['amount'] = '$'.number_format($data->spend, 2);
                    $finalArray[$key]['volume_rebate'] = '$'.number_format($data->volume_rebate, 2);
                    $finalArray[$key]['commission'] = '<div class="d-flex align-items-center"><button type="button" class="btn btn-primary" id="commission_rebate_id" data-id="['.$data->ids.']" data-bs-toggle="modal" data-bs-target="#staticBackdrop">$'.number_format($data->commission, 2).'</button> <button data-id="['.$data->ids.']" id="downloadCsvBtn" class="ms-2 btn btn-primary" >Download Report</button></div>';
                }
                $ids++;
            }
                
            if ($filter['quarter'] == 'Annual' && !$csv) {
                $finalArray = [];
                $finalArrays['end_date'] = date_format(date_create($endDate), 'm/d/Y');
                $finalArrays['start_date'] = date_format(date_create($startDate), 'm/d/Y');
                $finalArrays['approved'] = (($annual["approved"] == 0) ? ('No') : ('Yes'));
                $finalArrays['paid'] = (($annual["paid"] == 0) ? ('No') : ('Yes'));
                $finalArrays['sales_rep'] = $salesRep->sales_rep;
                $finalArrays['amount'] = '$'.number_format($annual["spend"], 2);
                $finalArrays['volume_rebate'] = '$'.number_format($annual["volume_rebate"], 2);
                $finalArrays['commission'] = '<div class="d-flex align-items-center"><button type="button" class="btn btn-primary" id="commission_rebate_id" data-id="['.$annual["ids"].']" data-bs-toggle="modal" data-bs-target="#staticBackdrop">$'.number_format($annual["commission"], 2).'</button> <button data-id="['.$annual["ids"].']" id="downloadCsvBtn" class="ms-2 btn btn-primary" >Download Report</button></div>';
                $finalArray[] = $finalArrays;
            }

            /** Defining returning final array for datatable */
            return ['data' => $finalArray, 'recordsTotal' => $totalRecords, 'recordsFiltered' => $totalRecords];
        } else {
            return ['data' => [], 'recordsTotal' => 0, 'recordsFiltered' => 0];
        }
    }

    public static function getCommissionReportFilterdDataSecond($filter = [], $csv = false){
        /** Define column array for ordering the rows and searching the rows */
        $orderColumnArray = [
            0 => 'commission_rebate_detail.account_name',
            1 => 'suppliers.supplier_name',
            2 => 'commission_rebate_detail.spend',
            3 => 'commission_rebate_detail.volume_rebate',
            4 => 'commission_rebate_detail.commission',
            5 => 'commission_rebate_detail.start_date',
            6 => 'commission_rebate_detail.end_date',
        ];

        if ($csv) {
            /** Create query for CSV export with specific columns */
            $query = CommissionRebateDetail::query()->selectRaw(
                "`commission_rebate_detail`.`spend` AS `amount`, 
                `commission_rebate_detail`.`volume_rebate` AS `volume_rebate`,
                `commission_rebate_detail`.`commission` AS `commissions`,
                `commission_rebate_detail`.`commission_percentage` AS `commission`,
                `commission_rebate_detail`.`volume_rebate_percentage` AS `volume_rebates`,
                `suppliers`.`supplier_name` AS `supplier_name`,
                `commission_rebate_detail`.`start_date` as start_date,
                `commission_rebate_detail`.`end_date` as end_date,
                `commission_rebate_detail`.`commission_start_date` as `commission_start_date`,
                `commission_rebate_detail`.`commission_end_date` as `commission_end_date`,
                `commission_rebate_detail`.`quarter` as quarter,
                `commission_rebate_detail`.`account_name` as account_name,
                `commission_rebate_detail`.`month` as month,
                `commission_rebate_detail`.`approved` as approved,
                `commission_rebate_detail`.`paid` as paid,
                CONCAT(`users`.`first_name`, ' ', `users`.`last_name`) AS `approved_by`"
            );

            $query->leftJoin('users', 'users.id', '=', 'commission_rebate_detail.paid_by');
        } else {
            /** Create query for normal data retrieval with aggregated columns */
            $query = CommissionRebateDetail::query()->selectRaw(
                "SUM(`commission_rebate_detail`.`spend`) AS `amount`, 
                SUM(`commission_rebate_detail`.`volume_rebate`) AS `volume_rebate`,
                SUM(`commission_rebate_detail`.`commission`) AS `commissions`,
                `commission_rebate_detail`.`commission_percentage` AS `commission`,
                `commission_rebate_detail`.`volume_rebate_percentage` AS `volume_rebates`,
                `suppliers`.`supplier_name` AS `supplier_name`,
                `commission_rebate_detail`.`start_date` as start_date,
                `commission_rebate_detail`.`end_date` as end_date,
                `commission_rebate_detail`.`quarter` as quarter,
                `commission_rebate_detail`.`account_name` as account_name,
                `commission_rebate_detail`.`month` as month,
                `commission_rebate_detail`.`approved` as approved,
                `commission_rebate_detail`.`paid` as paid"
            );
        }

        $query->leftJoin('suppliers', 'suppliers.id', '=', 'commission_rebate_detail.supplier');
    
         /** Year and quarter filter here */
         if (isset($filter['year']) || !empty($filter['quarter'])) {
            $year = $filter['year'];
            $res[1] =['January', 'February', 'March'];
            $res[2] = ['April', 'May', 'June'];
            $res[3] = ['July', 'August', 'September'];
            $res[4] = ['October', 'November', 'December'];

            $monthDates = [];
            for ($month = 1; $month <= 12; $month++) {
                $start = date('Y-m-01', strtotime("$year-$month-01"));
                $end = date('Y-m-t', strtotime("$year-$month-01"));

                $monthDates[] = ['start_date' => $start, 'end_date' => $end];
            }

            if ($filter['quarter'] == 'Quarter 1') {
                $startDate = $monthDates[0]['start_date'];
                $endDate = $monthDates[2]['end_date'];
            }

            if ($filter['quarter'] == 'Quarter 2') {
                $startDate = $monthDates[3]['start_date'];
                $endDate = $monthDates[5]['end_date'];
            }

            if ($filter['quarter'] == 'Quarter 3') {
                $startDate = $monthDates[6]['start_date'];
                $endDate = $monthDates[8]['end_date'];
            }

            if ($filter['quarter'] == 'Quarter 4') {
                $startDate = $monthDates[9]['start_date'];
                $endDate = $monthDates[11]['end_date'];
            }

            if ($filter['quarter'] == 'Annual') {
                $startDate = $monthDates[0]['start_date'];
                $endDate = $monthDates[11]['end_date'];
                $query->where('spend', '!=', 0);    
            }

            /** Apply date filter to the query */
            $query->whereDate('commission_rebate_detail.start_date', '>=', $startDate)
            ->whereDate('commission_rebate_detail.end_date', '<=', $endDate);
        }

        /** Filter the data on the bases of commission_rebate_id */
        if (isset($filter['commission_rebate_id']) && !empty($filter['commission_rebate_id'])) {
            if (is_string($filter['commission_rebate_id'])) {
                $filter['commission_rebate_id'] = explode(',', $filter['commission_rebate_id']);
            }

            $query->whereIn('commission_rebate_detail.commission_rebate_id', $filter['commission_rebate_id']);
        }

        if (isset($filter['sales_reps']) && !empty($filter['sales_reps'])) {
            $query->where('commission_rebate_detail.sales_rep', $filter['sales_reps']);
        }

        // $query->groupBy('suppliers.id');
        /** Group by necessary fields */
        if (!$csv) {
            $query->groupBy('commission_rebate_detail.account_name', 'commission_rebate_detail.supplier');
        }

        // dd($query->toSql(), $query->getBindings());

        /** Selecting total record for pagination */
        $totalRecords = $query->getQuery()->getCountForPagination();

        /** Order by column and direction */
        if (isset($filter['order'][0]['column']) && isset($orderColumnArray[$filter['order'][0]['column']]) && isset($filter['order'][0]['dir'])) {
            if (in_array($filter['order'][0]['column'], [2, 3, 4])) {
                $query->orderBy(DB::raw('CAST('.$orderColumnArray[$filter['order'][0]['column']].' AS DECIMAL(10, 2))'), $filter['order'][0]['dir']);
            } else {
                $query->orderBy($orderColumnArray[$filter['order'][0]['column']], $filter['order'][0]['dir']);
            }
        } else {
            $query->orderBy($orderColumnArray[0], 'asc');
        }

        $formatuserdata = $query->when(isset($filter['start']) && isset($filter['length']), function ($query) use ($filter) {
            return $query->skip($filter['start'])->take($filter['length']);
        })->get();
        
        $finalArray=[];
        /** Making final array */
        if (isset($formatuserdata) && !empty($formatuserdata)) {
            $paid = false;
            foreach ($formatuserdata as $key => $value) {
                if ($csv) {
                    $finalArray[$key]['supplier'] = $value->supplier_name;
                    $finalArray[$key]['account_name'] = $value->account_name;
                    $finalArray[$key]['amount'] = $value->amount;
                    $finalArray[$key]['approved_by'] = $value->approved_by;
                    $finalArray[$key]['commission_end_date'] = date_format(date_create($value->commission_end_date), 'm/d/Y');
                    $finalArray[$key]['commission_start_date'] = date_format(date_create($value->commission_start_date), 'm/d/Y');
                    $finalArray[$key]['end_date'] = date_format(date_create($value->end_date), 'm/d/Y');
                    $finalArray[$key]['commissions'] = $value->commissions;
                    $finalArray[$key]['commission'] = $value->commission;
                    $finalArray[$key]['quarter'] = $value->quarter;
                    $finalArray[$key]['start_date'] = date_format(date_create($value->start_date), 'm/d/Y');
                    $finalArray[$key]['volume_rebate'] = $value->volume_rebate;
                    $finalArray[$key]['month'] = $value->month;
                    $finalArray[$key]['approved'] = $value->approved;
                    $finalArray[$key]['paid'] = $value->paid;
                    if ($value->approved == 0 || $value->paid == 0) {
                        $paid = true;
                    }
                } else {
                    $finalArray[$key]['supplier'] = $value->supplier_name;
                    $finalArray[$key]['account_name'] = $value->account_name;
                    $finalArray[$key]['amount'] = '$'.number_format($value->amount, 2);
                    $finalArray[$key]['commission'] = '$'.number_format($value->commissions, 2);
                    $finalArray[$key]['volume_rebate'] = '$'.number_format($value->volume_rebate, 2) .' ('. $value->volume_rebates. '%)';
                }
            }
        }
    
        if ($csv) {
            $finalArray['paid_check'] = $paid;
            return $finalArray;
        } else {
            /** Defining returning final array for datatable */
            return [
                'data' => $finalArray,
                'recordsTotal' => $totalRecords, // Use count of formatted data for total records
                'recordsFiltered' => $totalRecords, // Use total records from the query
            ];
        }
    }

    public static function getAllCommission($filter = []){
        /** Initialize the query on the CommissionRebateDetail model, selecting the sum of commissions and the paid date */
        $query = CommissionRebateDetail::query()->selectRaw(
            "SUM(`commission_rebate_detail`.`commission`) AS `commissions`,
            paid_date"
        );

        /** Check if year or quarter filters are provided */
        if (isset($filter['year']) || !empty($filter['quarter'])) {
            $year = $filter['year'];

            /** Define the quarters with corresponding months */
            $res[1] =['January', 'February', 'March'];
            $res[2] = ['April', 'May', 'June'];
            $res[3] = ['July', 'August', 'September'];
            $res[4] = ['October', 'November', 'December'];
            $monthDates = [];

            /** Create start and end dates for each month of the given year */
            for ($month = 1; $month <= 12; $month++) {
                $start = date('Y-m-01', strtotime("$year-$month-01"));
                $end = date('Y-m-t', strtotime("$year-$month-01"));
                $monthDates[] = ['start_date' => $start, 'end_date' => $end];
            }

            /** Start date for the filter */
            $startDate = $monthDates[0]['start_date'];

            /** Determine the end date based on the selected quarter */
            if($filter['quarter'] == 'Quarter 1'){
                $endDate = $monthDates[2]['end_date'];
            }

            if($filter['quarter'] == 'Quarter 2'){
                $endDate = $monthDates[5]['end_date'];
            }

            if($filter['quarter'] == 'Quarter 3'){
                $endDate = $monthDates[8]['end_date'];
            }

            if($filter['quarter'] == 'Quarter 4'){
                $endDate = $monthDates[11]['end_date'];
            }

            if ($filter['quarter'] == 'Annual'){
                $endDate = $monthDates[11]['end_date'];
                $query->where('spend', '!=', 0);    
            }

            /** Apply the date range filter to the query */
            $query->whereDate('commission_rebate_detail.start_date', '>=', $startDate)
                ->whereDate('commission_rebate_detail.start_date', '<=', $endDate);
        }

        /** Apply sales representative filter if provided */
        if (isset($filter['sales_reps']) && !empty($filter['sales_reps'])) {
            $query->where('commission_rebate_detail.sales_rep', $filter['sales_reps']);
        }

        /** Execute the query and get the first result */
        $record = $query->first();

        /** Prepare the final result array with the calculated commissions and paid date */
        $finallArray['commissions'] = $record->commissions;
        $finallArray['paid_date'] = $record->paid_date;

        /** Return the result array */
        return $finallArray;
    }

    public static function getConsolidatedReportFilterdData($filter = [], $csv = false) {
        /** Define column array for ordering the rows and searching the rows */
        $orderColumnArray = [
            0 => 'suppliers.supplier_name',
            1 => 'master_account_detail.account_name',
            2 => 'spend',
        ];

        /** Define supplier categories array for categorizing the data */
        $supplierColumnArray = [
            1 => 'Office Supplies',
            2 => 'MRO',
            3 => 'Office Supplies',
            4 => 'Office Supplies',
            5 => 'Office Supplies',
            6 => 'Office Supplies',
            7 => 'Office Supplies',
            8 => 'Car Rental',
            9 => 'Energy Services',
            10 => 'MRO',
            11 => 'Wireless',
            12 => 'Packaging',
        ];

        $query = self::query()
        ->selectRaw(
            "suppliers.supplier_name as supplier_name,
            suppliers.id as supplier_id,
            master_account_detail.account_name as account_name,
            SUM(`orders`.`amount`) as spend"
        );

        $query->leftJoin('master_account_detail', 'orders.customer_number', '=', 'master_account_detail.account_number')
        ->leftJoin('suppliers', 'suppliers.id', '=', 'orders.supplier_id');

        /** Year and quarter filter here */
        if (isset($filter['start_date']) && !empty($filter['start_date']) && isset($filter['end_date']) && !empty($filter['end_date'])) {
            $endDate = $filter['end_date'];
            $startDate = $filter['start_date'];
      
            $query->whereBetween('orders.date', [$startDate, $endDate]);
        }

        $totalRecords = 0;
        /** Filter by account name if provided */
        if (isset($filter['account_name']) && !empty($filter['account_name']) && $filter['account_name'] != 'null') {
            $query->where('master_account_detail.account_name', $filter['account_name']);
        }

        // $query->groupBy('orders.supplier_id', 'master_account_detail.account_name');

        $totalRecords = $query->getQuery()->getCountForPagination();
        
        if (isset($filter['supplier_id']) && in_array('all', $filter['supplier_id'])) {

            /** Filter for specific supplier IDs */
            $query->whereIn('orders.supplier_id', [1, 2, 3, 4, 5, 6, 7]);
        } elseif (isset($filter['supplier_id']) && !empty($filter['supplier_id']) && !in_array('all', $filter['supplier_id'])) {
            if (isset($filter['supplier_id'][1])) {

                /** Filter for specified supplier IDs */
                $query->whereIn('orders.supplier_id', $filter['supplier_id']);
            } else {

                /** Filter for specified supplier IDs */
                $query->where('orders.supplier_id', $filter['supplier_id'][0]);
            }
            
        } else {
            if ($csv == true) {
                $finalArray['heading'] = [
                    'Supplier Name',
                    'Account Name',
                    'Spend',
                    'Category',
                ];

                return $finalArray;
            } else {
                /** Return empty data if no filters match */
                return [
                    'data' => [],
                    'recordsTotal' => $totalRecords,
                    'recordsFiltered' => 0,
                ];
            }
        }


        /** Order by specified column and direction */
        if (isset($filter['order'][0]['column']) && isset($orderColumnArray[$filter['order'][0]['column']]) && isset($filter['order'][0]['dir'])) {
            $query->orderBy($orderColumnArray[$filter['order'][0]['column']], $filter['order'][0]['dir']);
        } else {
            $query->orderBy($orderColumnArray[0], 'asc');
        }

        /** Group by with order supplier id */
        $query->groupBy($orderColumnArray[1], 'orders.supplier_id');

        /** Get the filtered records count */
        $filteredRecords = $query->getQuery()->getCountForPagination();

        /** Paginate the results if pagination filters are provided */
        $queryData = $query->when(isset($filter['start']) && isset($filter['length']), function ($query) use ($filter) {
            return $query->skip($filter['start'])->take($filter['length']);
        })->get();

        $totalAmount = 0;
        $finalArray = [];
        foreach ($queryData as $key => $value) {
            if($csv) {
                /** Prepare the final array for CSV */
                $finalArray[$key]['supplier_name'] = $value->supplier_name;
                $finalArray[$key]['account_name'] = $value->account_name;
                $finalArray[$key]['spend'] = $value->spend;
                $finalArray[$key]['category'] = $supplierColumnArray[$value->supplier_id];
            } else {
                /** Calculating total amount */
                $totalAmount += $value->spend;

                /** Prepare the final array for non-CSV */
                $finalArray[$key]['supplier_name'] = $value->supplier_name;
                $finalArray[$key]['account_name'] = $value->account_name;
                $finalArray[$key]['spend'] =  '$'.number_format($value->spend, 2);
                $finalArray[$key]['category'] = $supplierColumnArray[$value->supplier_id];
                $finalArray[$key]['download'] = "<button class='btn btn-success' onClick='downloadFile(\"" . $value->account_name . "\", \"" . $value->supplier_id . "\", \"" . $startDate . "\", \"" . $endDate . "\")'>Download</button>";
            }
        }
        // dd($query->toSql(), $query->getBindings());
        // dd($finalArray);
        if(!$csv && isset($finalArray[0])) {
            $finalArray[0]['spend'] .= '<input type="hidden" class="total_amount" value="' . number_format($totalAmount, 2) . '">';
        }
        
        if ($csv == true) {
            /** CSV header definition */
            $finalArray['heading'] = [
                'Supplier Name',
                'Account Name',
                'Spend',
                'Category',
            ];
            return $finalArray;
        } else {
            /** Return the result along with total and filtered counts */
            return [
                'data' => $finalArray,
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
            ];
        }
    }

    public static function getConsolidatedDownloadData($filter=[]) {
        /** Increasing the memory limit becouse memory limit issue */
        ini_set('memory_limit', '1024M');
        $query = self::query()
        ->selectRaw(
            "`order_product_details`.`order_id` as `id`,
            `order_product_details`.`key` as `key`,
            `order_product_details`.`value` as `value`"
        );

        $query->leftJoin('master_account_detail', 'orders.customer_number', '=', 'master_account_detail.account_number')
        ->leftJoin('order_product_details', 'order_product_details.order_id', '=', 'orders.id');

        /** Year and quarter filter here */
        if (isset($filter['start_date']) && !empty($filter['start_date']) && isset($filter['end_date']) && !empty($filter['end_date'])) {
            $endDate = $filter['end_date'];
            $startDate = $filter['start_date'];
      
            $query->whereBetween('orders.date', [$startDate, $endDate]);
        }

        /** Filter by account name if provided */
        if (isset($filter['account_name']) && !empty($filter['account_name']) && $filter['account_name'] != 'null') {
            $query->where('master_account_detail.account_name', $filter['account_name']);
        }

        /** Filter for specified supplier IDs */
        if (isset($filter['supplier_id']) && !empty($filter['supplier_id'])) {
            $query->where('orders.supplier_id', $filter['supplier_id']);
        } 

        /** Getting result */
        $queryData = $query->get();
        // dd($queryData);
        /** Creating new array */
        if ($filter['supplier_id'] == 4) {
            $finalArray = [];

            // $outputArray = [];
            
                foreach ($queryData as $key => $value) {
                    if ("Div ID" == rtrim($value->key, " ID")) {
                        /** Prepare the final array for CSV */
                                   if (preg_match('/\bdate\b/i', $value->key)) {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->value))->format('Y-m-d H:i:s');
                                   } else {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = $value->value;
                                   }
                   } else {
                     $finalArray[$value->id]["Div ID"] = '';
                   }
                   if ("Master_Customer_Number" == rtrim($value->key, " ID")) {
                        /** Prepare the final array for CSV */
                                   if (preg_match('/\bdate\b/i', $value->key)) {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->value))->format('Y-m-d H:i:s');
                                   } else {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = $value->value;
                                   }
                   } else {
                     $finalArray[$value->id]["Master_Customer_Number"] = '';
                   }
                   if ("Master Customer Name" == rtrim($value->key, " ID")) {
                        /** Prepare the final array for CSV */
                                   if (preg_match('/\bdate\b/i', $value->key)) {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->value))->format('Y-m-d H:i:s');
                                   } else {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = $value->value;
                                   }
                   } else {
                     $finalArray[$value->id]["Master Customer Name"] = '';
                   }
                   if ("Bill To Number" == rtrim($value->key, " ID")) {
                        /** Prepare the final array for CSV */
                                   if (preg_match('/\bdate\b/i', $value->key)) {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->value))->format('Y-m-d H:i:s');
                                   } else {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = $value->value;
                                   }
                   } else {
                     $finalArray[$value->id]["Bill To Number"] = '';
                   }
                   if ("Bill To Name" == rtrim($value->key, " ID")) {
                        /** Prepare the final array for CSV */
                                   if (preg_match('/\bdate\b/i', $value->key)) {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->value))->format('Y-m-d H:i:s');
                                   } else {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = $value->value;
                                   }
                   } else {
                     $finalArray[$value->id]["Bill To Name"] = '';
                   }
                   if ("Ship To Number" == rtrim($value->key, " ID")) {
                        /** Prepare the final array for CSV */
                                   if (preg_match('/\bdate\b/i', $value->key)) {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->value))->format('Y-m-d H:i:s');
                                   } else {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = $value->value;
                                   }
                   } else {
                     $finalArray[$value->id]["Ship To Number"] = '';
                   }
                   if ("Ship To Name" == rtrim($value->key, " ID")) {
                        /** Prepare the final array for CSV */
                                   if (preg_match('/\bdate\b/i', $value->key)) {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->value))->format('Y-m-d H:i:s');
                                   } else {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = $value->value;
                                   }
                   } else {
                     $finalArray[$value->id]["Ship To Name"] = '';
                   }
                   if ("Ship To Line1 Address" == rtrim($value->key, " ID")) {
                        /** Prepare the final array for CSV */
                                   if (preg_match('/\bdate\b/i', $value->key)) {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->value))->format('Y-m-d H:i:s');
                                   } else {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = $value->value;
                                   }
                   } else {
                     $finalArray[$value->id]["Ship To Line1 Address"] = '';
                   }
                   if ("Ship To Line2 Address" == rtrim($value->key, " ID")) {
                        /** Prepare the final array for CSV */
                                   if (preg_match('/\bdate\b/i', $value->key)) {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->value))->format('Y-m-d H:i:s');
                                   } else {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = $value->value;
                                   }
                   } else {
                     $finalArray[$value->id]["Ship To Line2 Address"] = '';
                   }
                   if ("Ship To Line3 Address" == rtrim($value->key, " ID")) {
                        /** Prepare the final array for CSV */
                                   if (preg_match('/\bdate\b/i', $value->key)) {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->value))->format('Y-m-d H:i:s');
                                   } else {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = $value->value;
                                   }
                   } else {
                     $finalArray[$value->id]["Ship To Line3 Address"] = '';
                   }
                   if ("Ship To City" == rtrim($value->key, " ID")) {
                        /** Prepare the final array for CSV */
                                   if (preg_match('/\bdate\b/i', $value->key)) {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->value))->format('Y-m-d H:i:s');
                                   } else {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = $value->value;
                                   }
                   } else {
                     $finalArray[$value->id]["Ship To City"] = '';
                   }
                   if ("Ship To State" == rtrim($value->key, " ID")) {
                        /** Prepare the final array for CSV */
                                   if (preg_match('/\bdate\b/i', $value->key)) {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->value))->format('Y-m-d H:i:s');
                                   } else {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = $value->value;
                                   }
                   } else {
                     $finalArray[$value->id]["Ship To State"] = '';
                   }
                   if ("Ship To Zip" == rtrim($value->key, " ID")) {
                        /** Prepare the final array for CSV */
                                   if (preg_match('/\bdate\b/i', $value->key)) {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->value))->format('Y-m-d H:i:s');
                                   } else {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = $value->value;
                                   }
                   } else {
                     $finalArray[$value->id]["Ship To Zip"] = '';
                   }
                   if ("Vendor Part Number" == rtrim($value->key, " ID")) {
                        /** Prepare the final array for CSV */
                                   if (preg_match('/\bdate\b/i', $value->key)) {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->value))->format('Y-m-d H:i:s');
                                   } else {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = $value->value;
                                   }
                   } else {
                     $finalArray[$value->id]["Vendor Part Number"] = '';
                   }
                   if ("Item Description" == rtrim($value->key, " ID")) {
                        /** Prepare the final array for CSV */
                                   if (preg_match('/\bdate\b/i', $value->key)) {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->value))->format('Y-m-d H:i:s');
                                   } else {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = $value->value;
                                   }
                   } else {
                     $finalArray[$value->id]["Item Description"] = '';
                   }
                   if ("Primary Product Hierarchy" == rtrim($value->key, " ID")) {
                        /** Prepare the final array for CSV */
                                   if (preg_match('/\bdate\b/i', $value->key)) {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->value))->format('Y-m-d H:i:s');
                                   } else {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = $value->value;
                                   }
                   } else {
                     $finalArray[$value->id]["Primary Product Hierarchy"] = '';
                   }
                   if ("Diversity" == rtrim($value->key, " ID")) {
                        /** Prepare the final array for CSV */
                                   if (preg_match('/\bdate\b/i', $value->key)) {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->value))->format('Y-m-d H:i:s');
                                   } else {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = $value->value;
                                   }
                   } else {
                     $finalArray[$value->id]["Diversity"] = '';
                   }
                   if ("Diversity Code" == rtrim($value->key, " ID")) {
                        /** Prepare the final array for CSV */
                                   if (preg_match('/\bdate\b/i', $value->key)) {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->value))->format('Y-m-d H:i:s');
                                   } else {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = $value->value;
                                   }
                   } else {
                     $finalArray[$value->id]["Diversity Code"] = '';
                   }
                   if ("Diversity Sub Type Cd" == rtrim($value->key, " ID")) {
                        /** Prepare the final array for CSV */
                                   if (preg_match('/\bdate\b/i', $value->key)) {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->value))->format('Y-m-d H:i:s');
                                   } else {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = $value->value;
                                   }
                   } else {
                     $finalArray[$value->id]["Diversity Sub Type Cd"] = '';
                   }
                   if ("Selling Unit Measure Qty" == rtrim($value->key, " ID")) {
                        /** Prepare the final array for CSV */
                                   if (preg_match('/\bdate\b/i', $value->key)) {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->value))->format('Y-m-d H:i:s');
                                   } else {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = $value->value;
                                   }
                   } else {
                     $finalArray[$value->id]["Selling Unit Measure Qty"] = '';
                   }
                   if ("Vendor Name" == rtrim($value->key, " ID")) {
                        /** Prepare the final array for CSV */
                                   if (preg_match('/\bdate\b/i', $value->key)) {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->value))->format('Y-m-d H:i:s');
                                   } else {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = $value->value;
                                   }
                   } else {
                     $finalArray[$value->id]["Vendor Name"] = '';
                   }
                   if ("Recycled Flag" == rtrim($value->key, " ID")) {
                        /** Prepare the final array for CSV */
                                   if (preg_match('/\bdate\b/i', $value->key)) {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->value))->format('Y-m-d H:i:s');
                                   } else {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = $value->value;
                                   }
                   } else {
                     $finalArray[$value->id]["Recycled Flag"] = '';
                   }
                   if ("Recycled %" == rtrim($value->key, " ID")) {
                        /** Prepare the final array for CSV */
                                   if (preg_match('/\bdate\b/i', $value->key)) {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->value))->format('Y-m-d H:i:s');
                                   } else {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = $value->value;
                                   }
                   } else {
                     $finalArray[$value->id]["Recycled %"] = '';
                   }
                   if ("Product Post Consumer Content %" == rtrim($value->key, " ID")) {
                        /** Prepare the final array for CSV */
                                   if (preg_match('/\bdate\b/i', $value->key)) {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->value))->format('Y-m-d H:i:s');
                                   } else {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = $value->value;
                                   }
                   } else {
                     $finalArray[$value->id]["Product Post Consumer Content %"] = '';
                   }
                   if ("Remanufactured/Refurbished Flag" == rtrim($value->key, " ID")) {
                        /** Prepare the final array for CSV */
                                   if (preg_match('/\bdate\b/i', $value->key)) {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->value))->format('Y-m-d H:i:s');
                                   } else {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = $value->value;
                                   }
                   } else {
                     $finalArray[$value->id]["Remanufactured/Refurbished Flag"] = '';
                   }
                   if ("ECO Feature" == rtrim($value->key, " ID")) {
                        /** Prepare the final array for CSV */
                                   if (preg_match('/\bdate\b/i', $value->key)) {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->value))->format('Y-m-d H:i:s');
                                   } else {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = $value->value;
                                   }
                   } else {
                     $finalArray[$value->id]["ECO Feature"] = '';
                   }
                   if ("ECO Sub Feature" == rtrim($value->key, " ID")) {
                        /** Prepare the final array for CSV */
                                   if (preg_match('/\bdate\b/i', $value->key)) {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->value))->format('Y-m-d H:i:s');
                                   } else {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = $value->value;
                                   }
                   } else {
                     $finalArray[$value->id]["ECO Sub Feature"] = '';
                   }
                   if ("ECO ID" == rtrim($value->key, " ID")) {
                        /** Prepare the final array for CSV */
                                   if (preg_match('/\bdate\b/i', $value->key)) {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->value))->format('Y-m-d H:i:s');
                                   } else {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = $value->value;
                                   }
                   } else {
                     $finalArray[$value->id]["ECO ID"] = '';
                   }
                   if ("Budget Center Name" == rtrim($value->key, " ID")) {
                        /** Prepare the final array for CSV */
                                   if (preg_match('/\bdate\b/i', $value->key)) {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->value))->format('Y-m-d H:i:s');
                                   } else {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = $value->value;
                                   }
                   } else {
                     $finalArray[$value->id]["Budget Center Name"] = '';
                   }
                   if ("Invoice Date" == rtrim($value->key, " ID")) {
                        /** Prepare the final array for CSV */
                                   if (preg_match('/\bdate\b/i', $value->key)) {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->value))->format('Y-m-d H:i:s');
                                   } else {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = $value->value;
                                   }
                   } else {
                     $finalArray[$value->id]["Invoice Date"] = '';
                   }
                   if ("Invoice Number" == rtrim($value->key, " ID")) {
                        /** Prepare the final array for CSV */
                                   if (preg_match('/\bdate\b/i', $value->key)) {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->value))->format('Y-m-d H:i:s');
                                   } else {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = $value->value;
                                   }
                   } else {
                     $finalArray[$value->id]["Invoice Number"] = '';
                   }
                   if ("On Contract?" == rtrim($value->key, " ID")) {
                        /** Prepare the final array for CSV */
                                   if (preg_match('/\bdate\b/i', $value->key)) {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->value))->format('Y-m-d H:i:s');
                                   } else {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = $value->value;
                                   }
                   } else {
                     $finalArray[$value->id]["On Contract?"] = '';
                   }
                   if ("Order Contact" == rtrim($value->key, " ID")) {
                        /** Prepare the final array for CSV */
                                   if (preg_match('/\bdate\b/i', $value->key)) {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->value))->format('Y-m-d H:i:s');
                                   } else {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = $value->value;
                                   }
                   } else {
                     $finalArray[$value->id]["Order Contact"] = '';
                   }
                   if ("Order Contact Phone Number" == rtrim($value->key, " ID")) {
                        /** Prepare the final array for CSV */
                                   if (preg_match('/\bdate\b/i', $value->key)) {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->value))->format('Y-m-d H:i:s');
                                   } else {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = $value->value;
                                   }
                   } else {
                     $finalArray[$value->id]["Order Contact Phone Number"] = '';
                   }
                   if ("Order Date" == rtrim($value->key, " ID")) {
                        /** Prepare the final array for CSV */
                                   if (preg_match('/\bdate\b/i', $value->key)) {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->value))->format('Y-m-d H:i:s');
                                   } else {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = $value->value;
                                   }
                   } else {
                     $finalArray[$value->id]["Order Date"] = '';
                   }
                   if ("Order Method Description" == rtrim($value->key, " ID")) {
                        /** Prepare the final array for CSV */
                                   if (preg_match('/\bdate\b/i', $value->key)) {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->value))->format('Y-m-d H:i:s');
                                   } else {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = $value->value;
                                   }
                   } else {
                     $finalArray[$value->id]["Order Method Description"] = '';
                   }
                   if ("Order Number" == rtrim($value->key, " ID")) {
                        /** Prepare the final array for CSV */
                                   if (preg_match('/\bdate\b/i', $value->key)) {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->value))->format('Y-m-d H:i:s');
                                   } else {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = $value->value;
                                   }
                   } else {
                     $finalArray[$value->id]["Order Number"] = '';
                   }
                   if ("Payment Method Code1" == rtrim($value->key, " ID")) {
                        /** Prepare the final array for CSV */
                                   if (preg_match('/\bdate\b/i', $value->key)) {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->value))->format('Y-m-d H:i:s');
                                   } else {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = $value->value;
                                   }
                   } else {
                     $finalArray[$value->id]["Payment Method Code1"] = '';
                   }
                   if ("Payment Method Code" == rtrim($value->key, " ID")) {
                        /** Prepare the final array for CSV */
                                   if (preg_match('/\bdate\b/i', $value->key)) {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->value))->format('Y-m-d H:i:s');
                                   } else {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = $value->value;
                                   }
                   } else {
                     $finalArray[$value->id]["Payment Method Code"] = '';
                   }
                   if ("Sell UOM" == rtrim($value->key, " ID")) {
                        /** Prepare the final array for CSV */
                                   if (preg_match('/\bdate\b/i', $value->key)) {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->value))->format('Y-m-d H:i:s');
                                   } else {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = $value->value;
                                   }
                   } else {
                     $finalArray[$value->id]["Sell UOM"] = '';
                   }
                   if ("Ship To Contact" == rtrim($value->key, " ID")) {
                        /** Prepare the final array for CSV */
                                   if (preg_match('/\bdate\b/i', $value->key)) {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->value))->format('Y-m-d H:i:s');
                                   } else {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = $value->value;
                                   }
                   } else {
                     $finalArray[$value->id]["Ship To Contact"] = '';
                   }
                   if ("Shipped Date" == rtrim($value->key, " ID")) {
                        /** Prepare the final array for CSV */
                                   if (preg_match('/\bdate\b/i', $value->key)) {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->value))->format('Y-m-d H:i:s');
                                   } else {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = $value->value;
                                   }
                   } else {
                     $finalArray[$value->id]["Shipped Date"] = '';
                   }
                   if ("SKU" == rtrim($value->key, " ID")) {
                        /** Prepare the final array for CSV */
                                   if (preg_match('/\bdate\b/i', $value->key)) {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->value))->format('Y-m-d H:i:s');
                                   } else {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = $value->value;
                                   }
                   } else {
                     $finalArray[$value->id]["SKU"] = '';
                   }
                   if ("Transaction Source System1" == rtrim($value->key, " ID")) {
                        /** Prepare the final array for CSV */
                                   if (preg_match('/\bdate\b/i', $value->key)) {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->value))->format('Y-m-d H:i:s');
                                   } else {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = $value->value;
                                   }
                   } else {
                     $finalArray[$value->id]["Transaction Source System1"] = '';
                   }
                   if ("Transaction Source System" == rtrim($value->key, " ID")) {
                        /** Prepare the final array for CSV */
                                   if (preg_match('/\bdate\b/i', $value->key)) {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->value))->format('Y-m-d H:i:s');
                                   } else {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = $value->value;
                                   }
                   } else {
                     $finalArray[$value->id]["Transaction Source System"] = '';
                   }
                   if ("Group ID1" == rtrim($value->key, " ID")) {
                        /** Prepare the final array for CSV */
                                   if (preg_match('/\bdate\b/i', $value->key)) {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->value))->format('Y-m-d H:i:s');
                                   } else {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = $value->value;
                                   }
                   } else {
                     $finalArray[$value->id]["Group ID1"] = '';
                   }
                   if ("Group ID" == rtrim($value->key, " ID")) {
                        /** Prepare the final array for CSV */
                                   if (preg_match('/\bdate\b/i', $value->key)) {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->value))->format('Y-m-d H:i:s');
                                   } else {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = $value->value;
                                   }
                   } else {
                     $finalArray[$value->id]["Group ID"] = '';
                   }
                   if ("Qty" == rtrim($value->key, " ID")) {
                        /** Prepare the final array for CSV */
                                   if (preg_match('/\bdate\b/i', $value->key)) {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->value))->format('Y-m-d H:i:s');
                                   } else {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = $value->value;
                                   }
                   } else {
                     $finalArray[$value->id]["Qty"] = '';
                   }
                   if ("Adj Gross Sales" == rtrim($value->key, " ID")) {
                        /** Prepare the final array for CSV */
                                   if (preg_match('/\bdate\b/i', $value->key)) {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->value))->format('Y-m-d H:i:s');
                                   } else {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = $value->value;
                                   }
                   } else {
                     $finalArray[$value->id]["Adj Gross Sales"] = '';
                   }
                   if ("Avg Sell Price" == rtrim($value->key, " ID")) {
                        /** Prepare the final array for CSV */
                                   if (preg_match('/\bdate\b/i', $value->key)) {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->value))->format('Y-m-d H:i:s');
                                   } else {
                                       $finalArray[$value->id][rtrim($value->key, " ID")] = $value->value;
                                   }
                   } else {
                     $finalArray[$value->id]["Avg Sell Price"] = '';
                   }
                }
            
            
            
        } else {
            $finalArray = [];
            foreach ($queryData as $key => $value) {         
                /** Prepare the final array for CSV */
                if (preg_match('/\bdate\b/i', $value->key)) {
                    $finalArray[$value->id][$value->key] = Carbon::createFromTimestamp(ExcelDate::excelToTimestamp($value->value))->format('Y-m-d H:i:s');
                } else {
                    $finalArray[$value->id][$value->key] = $value->value;
                }
            }
        }

        // dd($outputArray);

        // dd($query->toSql(), $query->getBindings());

        /** CSV header definition */
        // $finalArray['heading'] = array_keys($finalArray[1]);

        return $finalArray;
    }
}
