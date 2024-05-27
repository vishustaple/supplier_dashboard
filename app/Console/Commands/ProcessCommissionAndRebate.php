<?php

namespace App\Console\Commands;

use Illuminate\Support\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\{SalesTeam, Order, CommissionRebate, CommissionRebateDetail, Account};


class ProcessCommissionAndRebate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-commission-and-rebate';

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
        $finalYear = 2024;
        for ($year = 2022; $year <= $finalYear; $year++) {
            print_r($year);
            $salesRep = SalesTeam::select('id as sales_rep')->get();
            $res[1] =['January', 'February', 'March'];
            $res[2] = ['April', 'May', 'June'];
            $res[3] = ['July', 'August', 'September'];
            $res[4] = ['October', 'November', 'December'];
            $monthDates = [];

            for ($month = 1; $month <= 12; $month++) {
                $start = date('Y-m-01', strtotime("$year-$month-01"));
                $end = date('Y-m-t', strtotime("$year-$month-01"));
                $monthName = date('F', strtotime("$year-$month-01"));
                $monthDates[] = ['month' => $monthName, 'start_date' => $start, 'end_date' => $end];
            }

            foreach ($salesRep as $key => $values) {
                foreach ($monthDates as $key2 => $filter) {
                    $query = Order::query()->selectRaw(
                        "SUM(`orders`.`amount`) AS `amount`, 
                        `m2`.`account_name` AS `account_name`,
                        ((SUM(`orders`.`amount`)) / 100) * MAX(`rebate`.`volume_rebate`) AS `volume_rebate`,
                        (((SUM(`orders`.`amount`)) / 100) * MAX(`rebate`.`volume_rebate`) / 100) * MAX(`commission`.`commission`) AS `commissions`,
                        `commission`.`commission` AS `commission`,
                        `rebate`.`volume_rebate` AS `volume_rebates`,
                        `rebate`.`incentive_rebate` AS `incentive_rebates`,
                        `suppliers`.`supplier_name` AS `supplier_name`,
                        `suppliers`.`id` AS `supplier_id`,
                        `commission`.`start_date` as start_date,
                        `commission`.`end_date` as end_date, 
                        `orders`.`date` AS `date`"
                    )
                    
                    ->leftJoin('master_account_detail as m2', 'orders.customer_number', '=', 'm2.account_number')
                    ->leftJoin('suppliers', 'suppliers.id', '=', 'orders.supplier_id')
                    ->leftJoin('rebate', 'rebate.account_name', '=', 'm2.account_name')
                    ->leftJoin('commission', function ($join) { $join->on('commission.supplier', '=', 'suppliers.id')->on('commission.account_name', '=', 'm2.account_name'); });
        
                    $query->where('commission.sales_rep', $values->sales_rep);
        
                    $query->whereIn('commission.supplier', [1,2,3,4,5,6,7]);      
                
                    /** Year and quarter filter here */
                    if (in_array($filter['month'], $res[1]) ) {
                        $filters['quarter'] = 1;
                    }

                    if (in_array($filter['month'], $res[2]) ) {
                        $filters['quarter'] = 2;
                    }

                    if (in_array($filter['month'], $res[3]) ) {
                        $filters['quarter'] = 3;
                    }

                    if (in_array($filter['month'], $res[4]) ) {
                        $filters['quarter'] = 4;
                    }

                    $query->whereBetween('orders.date', [$filter['start_date'], $filter['end_date']])
                    ->where('commission.start_date', '<=', DB::raw('orders.date'))
                    ->where('commission.end_date', '>=', DB::raw('orders.date'));
                
                    /** Group by with account name */
                    $query->groupBy('m2.account_name');
        
                    /** Calculating total volume rebate, total commission on rebate and total amount */
                    $totalAmount = $totalVolumeRebate = $totalCommissionRebate = 0;

                    foreach ($query->get() as $value) {
                        $totalAmount += $value->amount;
                        $totalVolumeRebate += $value->volume_rebate;
                        $totalCommissionRebate += $value->commissions;
                    }

                    $dataExistCheck = DB::table('commission_rebate')
                    ->whereYear('start_date', $year)
                    ->whereDate('start_date', '>=', $filter['start_date'])
                    ->whereDate('end_date', '<=', $filter['end_date'])
                    ->where('commission_rebate.sales_rep', $values->sales_rep)
                    ->first();

                    if (!empty($dataExistCheck)) {
                        if ($dataExistCheck->approved != 1 && $dataExistCheck->paid != 1) {
                            // echo ' update ';
                            CommissionRebate::where('id', $dataExistCheck->id)->update([
                                'paid' => 0,
                                'approved' => 0,
                                'spend' => $totalAmount,
                                'quarter' => $filters['quarter'],
                                'end_date' => $filter['end_date'],
                                'sales_rep' => $values->sales_rep,
                                'start_date' => $filter['start_date'],
                                'volume_rebate' => $totalVolumeRebate,
                                'commission' => $totalCommissionRebate,
                                'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                            ]);
                                    
                            $dataExistCheck2 = DB::table('commission_rebate_detail')
                            ->whereYear('start_date', $year)
                            ->whereDate('start_date', '>=', $filter['start_date'])
                            ->whereDate('end_date', '<=', $filter['end_date'])
                            ->where('commission_rebate_id', $dataExistCheck->id)
                            ->get();

                            foreach ($query->get() as $key1 => $value) {
                                echo ' update2 ';
                                echo ' '.$key1.' ';
                                print_r($dataExistCheck2);
                                if ($dataExistCheck2) {
                                    CommissionRebateDetail::where('id', $dataExistCheck2[$key1]->id)->update([
                                        'paid' => 0,
                                        'approved' => 0,
                                        'spend' => $value->amount,
                                        'month' =>  $filter['month'],
                                        'quarter' => $filters['quarter'],
                                        'end_date' => $filter['end_date'],
                                        'sales_rep' => $values->sales_rep,
                                        'supplier' => $value->supplier_id,
                                        'commission' => $value->commissions,
                                        'start_date' => $filter['start_date'],
                                        'account_name' => $value->account_name,
                                        'volume_rebate' => $value->volume_rebate,
                                        'commission_percentage' => $value->commission,
                                        'volume_rebate_percentage' => $value->volume_rebates,
                                        'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                    ]);
                                }
                            }
                        } else {
                            // echo " not update ";
                        }
                    } else {
                        if (!empty($totalAmount) && $totalAmount > 0) {
                            // echo " Adding new record ";
                            $newCommissionRebate = CommissionRebate::create([
                                'paid' => 0,
                                'approved' => 0,
                                'spend' => $totalAmount,
                                'quarter' => $filters['quarter'],
                                'end_date' => $filter['end_date'],
                                'sales_rep' => $values->sales_rep,
                                'start_date' => $filter['start_date'],
                                'volume_rebate' => $totalVolumeRebate,
                                'commission' => $totalCommissionRebate,
                                'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                            ]);
            
                            foreach ($query->get() as $value) {
                                CommissionRebateDetail::create([
                                    'paid' => 0,
                                    'approved' => 0,
                                    'spend' => $value->amount,
                                    'month' =>  $filter['month'],
                                    'quarter' => $filters['quarter'],
                                    'end_date' => $filter['end_date'],
                                    'sales_rep' => $values->sales_rep,
                                    'supplier' => $value->supplier_id,
                                    'commission' => $value->commissions,
                                    'start_date' => $filter['start_date'],
                                    'account_name' => $value->account_name,
                                    'volume_rebate' => $value->volume_rebate,
                                    'commission_percentage' => $value->commission,
                                    'commission_rebate_id' => $newCommissionRebate->id,
                                    'volume_rebate_percentage' => $value->volume_rebates,
                                    'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                ]);
                            }
                        }
                    }
                }
            }
        }
    
        // $account = Account::all();
        // foreach ($account as $key => $values) {
        //     $customers = DB::table('orders')
        //     ->where('supplier_id', 2)
        //     ->where('customer_number', 'like', '%' . $values->account_number . '%')
        //     ->get();
            
        //     if (isset($customers) && !$customers->isEmpty()) {
        //         foreach ($customers as $key => $value) {
        //             DB::table('orders')->where('customer_number', $value->customer_number)->update(['customer_number' => $values->account_number]);
        //         }
        //     } 
        //     continue;
        // }
    }   
}
