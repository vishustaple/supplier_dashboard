<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
        $query = self::query() // Replace YourModel with the actual model you are using for the data
        ->select('orders.id as id') 
        ->leftJoin('master_account_detail', 'orders.customer_number', '=', 'master_account_detail.account_number')
        ->leftJoin('order_product_details', 'orders.id', '=', 'order_product_details.order_id');

        if ($filter['supplier'] == 3) {
            $query->whereIn('key', ['Core Flag']);

            if ($filter['core'] == 1) {
                $query->whereIn('value', ['N', 'U']);
            } else {
                $query->whereIn('value', ['Y']);
            }
        } else if ($filter['supplier'] == 4) {
            $query->whereIn('key', ['On Contract? ID']);

            if ($filter['core'] == 1) {
                $query->whereIn('value', ['N']);
            } else {
                $query->whereIn('value', ['Y']);
            }
        } else if ($filter['supplier'] == 5) {
            $query->whereIn('key', ['Price Method']);

            if ($filter['core'] == 2) {
                $query->orWhere('value', 'LIKE', 'PPL%')->orWhere('value', 'LIKE', 'CTL%');
            } else {
                $query->orWhere('value', 'NOT LIKE', 'PPL%')->orWhere('value', 'NOT LIKE', 'CTL%');
            } 
        } else if ($filter['supplier'] == 2) {
            $query->whereIn('key', ['Active Price Point']);

            if ($filter['core'] == 2) {
                $query->whereIn('value', ['CSP']);
            }
        } else if ($filter['supplier'] == 1) {
            if ($filter['core'] == 1) {
                $query->whereIn('key', ['OFF-CORE SPEND']);
                $query->whereNotNull('value');
            } else {
                $query->whereIn('key', ['ON-CORE SPEND']);
                $query->whereNotNull('value');
            }
        } else {

        }
        

        if (isset($filter['year'])) {
            $query->whereYear('orders.date', $filter['year']);
        }

        if (isset($filter['account_name']) && !empty($filter['account_name'])) {
            $query->where('master_account_detail.account_name', $filter['account_name']);
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

        $query->groupBy('order_product_details.order_id');
        $query->orderBy('orders.amount', 'desc')->limit(100);
        //  dd($query->toSql(), $query->getBindings());
        $orderIdArray = [];
        // dd($orderIdArray);
        if ($query->get()) {
            foreach ($query->get() as $key => $value) {
                $orderIdArray[] = $value->id;
            }
        }

        $query1 = self::query()
        ->select(
            'orders.amount as total_spend',
            DB::raw("MAX(CASE 
                WHEN `key` = 'UOM' THEN `value`
                WHEN `key` = 'SUB GROUP 1' THEN `value`
                WHEN `key` = 'Billing Document' THEN `value`
                WHEN `key` = 'Uo M' THEN `value`
                WHEN `key` = 'Sell UOM ID' THEN `value` ELSE NULL END
            ) AS uom"),
            DB::raw("MAX(CASE 
                WHEN `key` = 'SKU' THEN `value`
                WHEN `key` = 'PRODUCT' THEN `value`
                WHEN `key` = 'Material' THEN `value`
                WHEN `key` = 'Item Num' THEN `value`
                WHEN `key` = 'SKU ID' THEN `value` ELSE NULL END
            ) AS sku"),
            DB::raw("MAX(CASE 
                WHEN `key` = 'Product Description' THEN `value`
                WHEN `key` = 'DESCRIPTION' THEN `value`
                WHEN `key` = 'Material Description' THEN `value`
                WHEN `key` = 'Item Name' THEN `value`
                WHEN `key` = 'Item Description ID' THEN `value` ELSE NULL END
            ) AS description"),
            DB::raw("MAX(CASE 
                WHEN `key` = 'CLASS' THEN `value`
                WHEN `key` = 'CATEGORIES' THEN `value`
                WHEN `key` = 'Material Segment' THEN `value`
                WHEN `key` = 'Category' THEN `value`
                WHEN `key` = 'Primary Product Hierarchy DESC' THEN `value` ELSE NULL END
            ) AS category"),
            DB::raw("MAX(CASE 
                WHEN `key` = 'QTY Shipped' THEN `value`
                WHEN `key` = 'QUANTITYSHIPPED' THEN `value`
                WHEN `key` = 'Billing Qty' THEN `value`
                WHEN `key` = 'Qty' THEN `value` ELSE NULL END
            ) AS quantity_purchased"),
            DB::raw("MAX(CASE 
                WHEN `key` = 'Unit Net Price' THEN `value` ELSE NULL END
            ) AS unit_price"),
            DB::raw("MAX(CASE 
                WHEN `key` = '(Unit) Web Price' THEN `value` ELSE NULL END
            ) AS web_price"),
        )

        ->leftJoin('order_product_details', 'orders.id', '=', 'order_product_details.order_id')
        ->whereIn('orders.id', $orderIdArray)
        ->groupBy('order_product_details.order_id')
        ->orderBy('orders.amount', 'desc');

        $queryData = $query1->get();
        // dd($queryData);
        $newFinalArray = [];
        foreach ($queryData as $key => $value) {
            $newFinalArray[] = [
                'sku' => $value->sku,
                'description' => $value->description,
                'uom' => $value->uom,
                'category' => $value->category,
                'quantity_purchased' => $value->quantity_purchased,
                'total_spend' => (($csv) ? ($value->total_spend) : ('$'.number_format($value->total_spend, 2))),
                'unit_price_q1_price' => (($csv) ? ($value->unit_price) : ('$'.number_format($value->unit_price, 2))),
                'unit_price_q2_price' => $value->unit_price_q2_price,
                'unit_price_q3_price' => $value->unit_price_q3_price,
                'unit_price_q4_price' => $value->unit_price_q4_price,
                'web_price_q1_price' => (($csv) ? ($value->web_price) : ('$'.number_format($value->web_price, 2))),
                'web_price_q2_price' => $value->web_price_q2_price,
                'web_price_q3_price' => $value->web_price_q3_price,
                'web_price_q4_price' => $value->web_price_q4_price,
                'lowest_price' => $value->lowest_price,
            ];
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
            3 => 'volume_rebate',
            4 => 'incentive_rebate',
        ];

        $query = self::query()->selectRaw(
            "SUM(`orders`.`amount`) AS `amount`, 
            `m2`.`account_name` AS `account_name`,
            ((SUM(`orders`.`amount`)) / 100) * MAX(`rebate`.`volume_rebate`) AS `volume_rebate`,
            ((SUM(`orders`.`amount`)) / 100) * MAX(`rebate`.`incentive_rebate`) AS `incentive_rebate`,
            `rebate`.`volume_rebate` AS `volume_rebates`,
            `rebate`.`incentive_rebate` AS `incentive_rebates`,
            `suppliers`.`supplier_name` AS `supplier_name`, 
            `orders`.`date` AS `date`"
        )

        ->leftJoin('master_account_detail as m2', 'orders.customer_number', '=', 'm2.account_number')
        // ->leftJoin('rebate', 'm2.account_name', '=', 'rebate.account_name')
        ->leftJoin('rebate', function($join) {
            $join->on('m2.account_name', '=', 'rebate.account_name')
            ->on('m2.category_supplier', '=', 'rebate.supplier');
        })

        ->leftJoin('suppliers', 'suppliers.id', '=', 'orders.supplier_id');
        if (isset($filter['supplier']) && $filter['supplier'] == 4){
            $query->leftJoin('order_product_details', 'order_product_details.order_id', '=', 'orders.id');
        }

        // $query->where('orders.id', '<=', 932806);

        if (isset($filter['supplier']) && !empty($filter['supplier'])) {
            $query->where('orders.supplier_id', $filter['supplier']);

            if ($filter['supplier'] == 4) {
                $query->whereIn('order_product_details.key', ['Transaction Source System DESC']);
                $query->whereNotIn('order_product_details.value', ['Staples Technology Solutions', 'Staples Promotional Products USA']);
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

        /** Year and quarter filter here */
        if (isset($filter['end_date']) && isset($filter['start_date'])) {
            $query->whereBetween('orders.date', [$filter['start_date'], $filter['end_date']]);
        }
    
        /** Group by with account name */
        $query->groupBy('m2.account_name');
        // dd($query->get()->toArray());
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

        /** Formatting this */
        $totalAmounts = number_format($totalAmount, 2, '.', false);
        $totalVolumeRebates = number_format($totalVolumeRebate, 2, '.', false);
        $totalIncentiveRebates = number_format($totalIncentiveRebate, 2, '.', false);

        $totalAmount = number_format($totalAmount, 2);
        $totalVolumeRebate = number_format($totalVolumeRebate, 2);
        $totalIncentiveRebate = number_format($totalIncentiveRebate, 2);

        // dd($query->toSql(), $query->getBindings());

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
                    $finalArray[$key]['volume_rebate'] = number_format($value->volume_rebate, 2, '.', false);
                    if ($filter['supplier'] == 3) {
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

            if ($filter['supplier'] == 3) {
                /** Defining heading array for csv genration */
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

        $salesRep = SalesTeam::select(DB::raw('CONCAT(sales_team.first_name, " ", sales_team.last_name) as sales_rep'))->where('id', $filter['sales_rep'])->first();

        // GROUP_CONCAT(CONCAT_WS('_', `id`)) as `id`',
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
            return [
                'data' => [],
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
            ];
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

            $res[1] =[
                'January',
                'February',
                'March',
            ];

            $res[2] = [
                'April',
                'May',
                'June',
            ];

            $res[3] = [
                'July',
                'August',
                'September',
            ];

            $res[4] = [
                'October',
                'November',
                'December',
            ];

            $monthDates = [];

            for ($month = 1; $month <= 12; $month++) {
                $start = date('Y-m-01', strtotime("$year-$month-01"));
                $end = date('Y-m-t', strtotime("$year-$month-01"));
        
                $monthDates[] = [
                    'start_date' => $start,
                    'end_date' => $end,
                ];
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

        if (isset($datas) && $datas->isNotEmpty()) {
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
    
                    $finalArray[$key]['sales_rep'] = $salesRep->sales_rep;
                    $finalArray[$key]['amount'] = '$'.number_format($data->spend, 2);
                    $finalArray[$key]['volume_rebate'] = '$'.number_format($data->volume_rebate, 2);
                    // if ($data->approved == 1) {
                        // $finalArray[$key]['commission'] = '<div class="d-flex align-items-center"><button type="button" class="btn btn-primary" id="commission_rebate_id" data-id="'.$data->id.'" data-bs-toggle="modal" data-bs-target="#staticBackdrop">$'.number_format($data->commission, 2).'</button> <a id="downloadCsvBtn" class="btn ms-2 btn-primary" href="'.route('commission-file.download', ['sales_rep' => $filter['sales_rep']]).'">Download Report</a></div>';
                    // } else {
                        $finalArray[$key]['commission'] = '<div class="d-flex align-items-center"><button type="button" class="btn btn-primary" id="commission_rebate_id" data-id="['.$data->ids.']" data-bs-toggle="modal" data-bs-target="#staticBackdrop">$'.number_format($data->commission, 2).'</button> <button data-id="['.$data->ids.']" id="downloadCsvBtn" class="ms-2 btn btn-primary" >Download Report</button></div>';
                    // }
                }
                $ids++;
            }
            /** Defining returning final array for datatable */
            return [
                'data' => $finalArray,
                'recordsTotal' => $totalRecords, // Use count of formatted data for total records
                'recordsFiltered' => $totalRecords, // Use total records from the query
            ];
        } else {
            return [
                'data' => [],
                'recordsTotal' => 0, // Use count of formatted data for total records
                'recordsFiltered' => 0, // Use total records from the query
            ];
        }
    }

    public static function getCommissionReportFilterdDataSecond($filter = [], $csv = false){
        /** Define column array for ordering the rows and searching the rows */
        $orderColumnArray = [
            0 => 'suppliers.supplier_name',
            1 => 'amount',
            2 => 'volume_rebate',
            3 => 'commissions',
            4 => 'start_date',
            5 => 'end_date',
        ];

        $query = CommissionRebateDetail::query()->selectRaw(
            "`commission_rebate_detail`.`spend` AS `amount`, 
            `commission_rebate_detail`.`volume_rebate` AS `volume_rebate`,
            `commission_rebate_detail`.`commission` AS `commissions`,
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
        )
        ->leftJoin('suppliers', 'suppliers.id', '=', 'commission_rebate_detail.supplier');
    
         /** Year and quarter filter here */
         if (isset($filter['year']) || !empty($filter['quarter'])) {
            $year = $filter['year'];

            $res[1] =[
                'January',
                'February',
                'March',
            ];

            $res[2] = [
                'April',
                'May',
                'June',
            ];

            $res[3] = [
                'July',
                'August',
                'September',
            ];

            $res[4] = [
                'October',
                'November',
                'December',
            ];

            $monthDates = [];

            for ($month = 1; $month <= 12; $month++) {
                $start = date('Y-m-01', strtotime("$year-$month-01"));
                $end = date('Y-m-t', strtotime("$year-$month-01"));
        
                $monthDates[] = [
                    'start_date' => $start,
                    'end_date' => $end,
                ];
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

            $query->whereDate('commission_rebate_detail.start_date', '>=', $startDate)
            ->whereDate('commission_rebate_detail.start_date', '<=', $endDate);
        }

        /** Filter the data on the bases of commission_rebate_id */
        if (isset($filter['commission_rebate_id']) && !empty($filter['commission_rebate_id'])) {
            $query->whereIn('commission_rebate_detail.commission_rebate_id', $filter['commission_rebate_id']);
        }

        // dd($filter['sales_rep']);
        if (isset($filter['sales_reps']) && !empty($filter['sales_reps'])) {
            $query->where('commission_rebate_detail.sales_rep', $filter['sales_reps']);
        }

        // dd($query->toSql(), $query->getBindings());

        /** Selecting total record for pagination */
        $totalRecords = $query->getQuery()->getCountForPagination();

        /** Get total records count (without filtering) */
        if (isset($filter['order'][0]['column']) && isset($orderColumnArray[$filter['order'][0]['column']]) && isset($filter['order'][0]['dir'])) {
            /** Order by column and direction */
            $query->orderBy($orderColumnArray[$filter['order'][0]['column']], $filter['order'][0]['dir']);
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
                    $finalArray[$key]['end_date'] = date_format(date_create($value->end_date), 'm/d/Y');
                    $finalArray[$key]['commissions'] = $value->commissions;
                    $finalArray[$key]['commission'] = $value->commission;
                    $finalArray[$key]['quarter'] = $value->quarter;
                    $finalArray[$key]['start_date'] = date_format(date_create($value->start_date), 'm/d/Y');
                    $finalArray[$key]['volume_rebate'] = $value->volume_rebate;
                    $finalArray[$key]['month'] = $value->month;
                    $finalArray[$key]['approved'] = $value->approved;
                    $finalArray[$key]['paid'] = $value->paid;
                    if ($value->approved == 0 || $value->paid == 0)  {
                        $paid = true;
                    }
                } else {
                    $finalArray[$key]['supplier'] = $value->supplier_name;
                    $finalArray[$key]['account_name'] = $value->account_name;
                    $finalArray[$key]['end_date'] = date_format(date_create($value->end_date), 'm/d/Y');
                    $finalArray[$key]['start_date'] = date_format(date_create($value->start_date), 'm/d/Y');
                    $finalArray[$key]['amount'] = '$'.number_format($value->amount, 2);
                    $finalArray[$key]['commission'] = '$'.number_format($value->commissions, 2).' ('.(!empty($value->commissions) ? ($value->commission.'%') : ('N/A')).')';
                    $finalArray[$key]['volume_rebate'] = '$'.number_format($value->volume_rebate, 2).' ('.(!empty($value->volume_rebates) ? ($value->volume_rebates.'%') : ('N/A')).')';
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
        $query = CommissionRebateDetail::query()->selectRaw(
            "SUM(`commission_rebate_detail`.`commission`) AS `commissions`,
            paid_date"
        );

        /** Year and quarter filter here */
        if (isset($filter['year']) || !empty($filter['quarter'])) {
            $year = $filter['year'];

            $res[1] =[
                'January',
                'February',
                'March',
            ];

            $res[2] = [
                'April',
                'May',
                'June',
            ];

            $res[3] = [
                'July',
                'August',
                'September',
            ];

            $res[4] = [
                'October',
                'November',
                'December',
            ];

            $monthDates = [];

            for ($month = 1; $month <= 12; $month++) {
                $start = date('Y-m-01', strtotime("$year-$month-01"));
                $end = date('Y-m-t', strtotime("$year-$month-01"));
        
                $monthDates[] = [
                    'start_date' => $start,
                    'end_date' => $end,
                ];
            }
            $startDate = $monthDates[0]['start_date'];
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

            $query->whereDate('commission_rebate_detail.start_date', '>=', $startDate)
            ->whereDate('commission_rebate_detail.start_date', '<=', $endDate);
        }

        if (isset($filter['sales_reps']) && !empty($filter['sales_reps'])) {
            $query->where('commission_rebate_detail.sales_rep', $filter['sales_reps']);
        }
        $record = $query->first();
        $finallArray['commissions'] = $record->commissions;
        $finallArray['paid_date'] = $record->paid_date;
        return $finallArray;
    }

    public static function getConsolidatedReportFilterdData($filter = [], $csv = false) {
        /** Define column array for ordering the rows and searching the rows */
        $orderColumnArray = [
            0 => 'suppliers.supplier_name',
            1 => 'master_account_detail.account_name',
            2 => 'spend',
            // 3 => '',
            4 => 'current_rolling_spend',
            5 => 'previous_rolling_spend',
        ];

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

        /** Year and quarter filter here */
        if (isset($filter['start_date']) && !empty($filter['start_date']) && isset($filter['end_date']) && !empty($filter['end_date'])) {
            $endDate = $filter['end_date'];
            $startDate = $filter['start_date'];

            // Assuming $startDate and $endDate are already calculated for the current quarter or year
            $prevStartDate = $endDate;
            $prevEndDate = date('Y-m-d H:i:s', strtotime('-1 year', strtotime($endDate)));
            $prevStartDate1 = $prevEndDate;
            $prevEndDate1 = date('Y-m-d H:i:s', strtotime('-1 year', strtotime($prevEndDate)));
        } else {
            $startDate = Carbon::now()->format('Y-m-d H:i:s');
            $endDate = date('Y-m-d H:i:s', strtotime('+1 year', strtotime($startDate)));
            $prevStartDate = $endDate;
            $prevEndDate = date('Y-m-d H:i:s', strtotime('-1 year', strtotime($endDate)));
            $prevStartDate1 = $prevEndDate;
            $prevEndDate1 = date('Y-m-d H:i:s', strtotime('-1 year', strtotime($prevEndDate)));
        }

        $query = self::query() // Replace YourModel with the actual model you are using for the data   
        ->selectRaw(
            'suppliers.supplier_name as supplier_name,
            suppliers.id as supplier_id,
            master_account_detail.account_name as account_name,
            SUM(CASE WHEN `orders`.`date` BETWEEN ? AND ? THEN `orders`.`amount` ELSE 0 END) AS spend,
            SUM(CASE WHEN `orders`.`date` BETWEEN ? AND ? THEN `orders`.`amount` ELSE 0 END) AS current_rolling_spend,
            SUM(CASE WHEN `orders`.`date` BETWEEN ? AND ? THEN `orders`.`amount` ELSE 0 END) AS previous_rolling_spend',
            [$startDate, $endDate, $prevStartDate, $prevEndDate, $prevStartDate1, $prevEndDate1]
        );

        $query->leftJoin('master_account_detail', 'orders.customer_number', '=', 'master_account_detail.account_number')
        ->leftJoin('suppliers', 'suppliers.id', '=', 'orders.supplier_id');

        $totalRecords = 0;
        if (isset($filter['supplier_id']) && in_array('all', $filter['supplier_id'])) {
            $totalRecords = $query->getQuery()->getCountForPagination();
            $query->whereIn('orders.supplier_id', [1, 2, 3, 4, 5, 6, 7]);
        } elseif (isset($filter['supplier_id']) && !empty($filter['supplier_id']) && !in_array('all', $filter['supplier_id'])) {
            $totalRecords = $query->getQuery()->getCountForPagination();
            $query->whereIn('orders.supplier_id', $filter['supplier_id']);
        } else {
            if ($csv == true) {
                $finalArray['heading'] = [
                    'Supplier Name',
                    'Account Name',
                    'Spend',
                    'Category',
                    'Current Rolling Spend',
                    'Previous Rolling Spend',
                ];

                return $finalArray;
            } else {
                return [
                    'data' => [],
                    'recordsTotal' => $totalRecords,
                    'recordsFiltered' => 0,
                ];
            }
        }

        /** Search functionality */
        if (isset($filter['search']['value']) && !empty($filter['search']['value'])) {
            $searchTerm = $filter['search']['value'];

            $query->where(function ($q) use ($searchTerm, $orderColumnArray) {
                foreach ($orderColumnArray as $column) {
                    if (in_array($column, ['current_rolling_spend', 'previous_rolling_spend', 'spend'])) {
                        $q->having('spend', 'LIKE', '%' . $searchTerm . '%');
                    } else {
                        $q->orWhere($column, 'LIKE', '%' . $searchTerm . '%');
                    }
                }
            });            
        }
        
        /** Get total records count (without filtering) */
        if (isset($filter['order'][0]['column']) && isset($orderColumnArray[$filter['order'][0]['column']]) && isset($filter['order'][0]['dir'])) {
            /** Order by column and direction */
            $query->orderBy($orderColumnArray[$filter['order'][0]['column']], $filter['order'][0]['dir']);
        } else {
            $query->orderBy($orderColumnArray[0], 'asc');
        }

        $query->groupBy($orderColumnArray[1], 'orders.supplier_id');

        $filteredRecords = $query->getQuery()->getCountForPagination();

        $queryData = $query->when(isset($filter['start']) && isset($filter['length']), function ($query) use ($filter) {
            return $query->skip($filter['start'])->take($filter['length']);
        })->get();

        $finalArray = [];
        foreach ($queryData as $key => $value) {
            if($csv) {
                $finalArray[$key]['supplier_name'] = $value->supplier_name;
                $finalArray[$key]['account_name'] = $value->account_name;
                $finalArray[$key]['spend'] = $value->spend;
                $finalArray[$key]['category'] = $supplierColumnArray[$value->supplier_id];
                $finalArray[$key]['current_rolling_spend'] = $value->current_rolling_spend;
                $finalArray[$key]['previous_rolling_spend'] = $value->previous_rolling_spend;
            } else {
                $finalArray[$key]['supplier_name'] = $value->supplier_name;
                $finalArray[$key]['account_name'] = $value->account_name;
                $finalArray[$key]['spend'] = '$'.number_format($value->spend, 2);
                $finalArray[$key]['category'] = $supplierColumnArray[$value->supplier_id];
                $finalArray[$key]['current_rolling_spend'] = '$'.number_format($value->current_rolling_spend, 2);
                $finalArray[$key]['previous_rolling_spend'] = '$'.number_format($value->previous_rolling_spend, 2);
            }
        }
        // dd($query->toSql(), $query->getBindings());
        // dd($finalArray);

        if ($csv == true) {
            $finalArray['heading'] = [
                'Supplier Name',
                'Account Name',
                'Spend',
                'Category',
                'Current Rolling Spend',
                'Previous Rolling Spend',
            ];
            return $finalArray;
        } else {
            // Return the result along with total and filtered counts
            return [
                'data' => $finalArray,
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
            ];
        }
    }
}
