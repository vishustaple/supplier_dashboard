<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\{Carbon, Facades\DB};
use App\Models\{SalesTeam, Order, CommissionRebate, CommissionRebateDetail};


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
        $finalYear = date('Y');
        for ($year = 2024; $year <= $finalYear; $year++) {
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
                        "SUM(`orders`.`cost`) AS `cost`, 
                        `m2`.`account_name` AS `account_name`,
                        ((SUM(`orders`.`cost`)) / 100) * MAX(`rebate`.`volume_rebate`) AS `volume_rebate`,
                        (((SUM(`orders`.`cost`)) / 100) * MAX(`rebate`.`volume_rebate`) / 100) * MAX(`commissions`.`commissions`) AS `commissionss`,
                        `commissions`.`commissions` AS `commissions`,
                        `rebate`.`volume_rebate` AS `volume_rebates`,
                        `rebate`.`incentive_rebate` AS `incentive_rebates`,
                        `suppliers`.`supplier_name` AS `supplier_name`,
                        `suppliers`.`id` AS `supplier_id`,
                        `commissions`.`start_date` as start_date,
                        `commissions`.`end_date` as end_date, 
                        `orders`.`date` AS `date`"
                    )
                    
                    ->leftJoin('master_account_detail as m2', 'orders.customer_number', '=', 'm2.account_number')
                    ->leftJoin('suppliers', 'suppliers.id', '=', 'orders.supplier_id')
                    ->join('rebate', function($join) {
                        $join->on('m2.account_name', '=', 'rebate.account_name')
                        ->on('m2.supplier_id', '=', 'rebate.supplier');
                    })
                    ->join('commissions', function ($join) { 
                        $join->on('commissions.supplier', '=', 'suppliers.id')
                        ->on('commissions.account_name', '=', 'm2.account_name');
                    });
        
                    $query->where('commissions.sales_rep', $values->sales_rep);
        
                    $query->whereIn('commissions.supplier', [1,2,3,4,5,6,7]);      
                
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
                    
                    $query->where(function ($query) use ($filter)  {
                        $query->where(function ($subQuery) use ($filter) {
                            $subQuery->where('commissions.start_date', '>=', $filter['start_date'])
                                     ->whereBetween('orders.date', [DB::raw('commissions.start_date'), $filter['end_date']]);
                        })->orWhere(function ($subQuery) use ($filter) {
                            $subQuery->where('commissions.start_date', '<', $filter['start_date'])
                                     ->whereBetween('orders.date', [$filter['start_date'], $filter['end_date']]);
                        });
                    })
                    ->where(function ($query) use ($filter) {
                        $query->where(function ($subQuery) use ($filter) {
                            $subQuery->where('commissions.end_date', '<', $filter['end_date'])
                                     ->whereBetween('orders.date', [$filter['start_date'], DB::raw('commissions.end_date')]);
                        })->orWhere('commissions.end_date', '>=', $filter['end_date']);
                    });
                
                    /** Group by with account name */
                    $query->groupBy('commissions.account_name', 'commissions.supplier');
                    print_r($query->get()->toArray());
                    /** For debug query */
                    // print_r($query->toSql(), $query->getBindings());

                    /** Calculating total volume rebate, total commissions on rebate and total cost */
                    $totalAmount = $totalVolumeRebate = $totalCommissionRebate = 0;
                    
                    continue;
                    // foreach ($query->get() as $value) {
                    //     $totalAmount += $value->cost;
                    //     $totalVolumeRebate += $value->volume_rebate;
                    //     $totalCommissionRebate += $value->commissionss;
                    // }

                    // $dataExistCheck = DB::table('commissions_rebate')
                    //     ->whereYear('start_date', $year)
                    //     ->whereDate('start_date', '>=', $filter['start_date'])
                    //     ->whereDate('end_date', '<=', $filter['end_date'])
                    //     ->where('commissions_rebate.sales_rep', $values->sales_rep)
                    //     ->first();

                    // if (!empty($dataExistCheck)) {
                    //     if ($dataExistCheck->approved != 1 && $dataExistCheck->paid != 1) {
                    //         CommissionRebate::where('id', $dataExistCheck->id)->update([
                    //             'paid' => 0,
                    //             'approved' => 0,
                    //             'spend' => $totalAmount,
                    //             'quarter' => $filters['quarter'],
                    //             'end_date' => $filter['end_date'],
                    //             'sales_rep' => $values->sales_rep,
                    //             'start_date' => $filter['start_date'],
                    //             'volume_rebate' => $totalVolumeRebate,
                    //             'commissions' => $totalCommissionRebate,
                    //             'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    //         ]);
                                    
                    //         $dataExistCheck2 = DB::table('commissions_rebate_detail')
                    //             ->whereYear('start_date', $year)
                    //             ->whereDate('start_date', '>=', $filter['start_date'])
                    //             ->whereDate('end_date', '<=', $filter['end_date'])
                    //             ->where('commissions_rebate_id', $dataExistCheck->id)
                    //             ->get();

                    //         $countArray = count($dataExistCheck2);
                    //         foreach ($query->get() as $key1 => $value) {
                    //             if ($dataExistCheck2 && $countArray > 0) {
                    //                 $countArray--;
                    //                 CommissionRebateDetail::where('id', $dataExistCheck2[$key1]->id)->update([
                    //                     'paid' => 0,
                    //                     'approved' => 0,
                    //                     'spend' => $value->cost,
                    //                     'month' =>  $filter['month'],
                    //                     'quarter' => $filters['quarter'],
                    //                     'end_date' => $filter['end_date'],
                    //                     'sales_rep' => $values->sales_rep,
                    //                     'supplier' => $value->supplier_id,
                    //                     'commissions' => $value->commissionss,
                    //                     'start_date' => $filter['start_date'],
                    //                     'account_name' => $value->account_name,
                    //                     'volume_rebate' => $value->volume_rebate,
                    //                     'commissions_end_date' => $value->end_date,
                    //                     'commissions_start_date' => $value->start_date,
                    //                     'commissions_percentage' => $value->commissions,
                    //                     'volume_rebate_percentage' => $value->volume_rebates,
                    //                     'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    //                 ]);
                    //             } else {
                    //                 CommissionRebateDetail::create([
                    //                     'paid' => 0,
                    //                     'approved' => 0,
                    //                     'spend' => $value->cost,
                    //                     'month' =>  $filter['month'],
                    //                     'quarter' => $filters['quarter'],
                    //                     'end_date' => $filter['end_date'],
                    //                     'sales_rep' => $values->sales_rep,
                    //                     'supplier' => $value->supplier_id,
                    //                     'commissions' => $value->commissionss,
                    //                     'start_date' => $filter['start_date'],
                    //                     'account_name' => $value->account_name,
                    //                     'volume_rebate' => $value->volume_rebate,
                    //                     'commissions_end_date' => $value->end_date,
                    //                     'commissions_start_date' => $value->start_date,
                    //                     'commissions_percentage' => $value->commissions,
                    //                     'commissions_rebate_id' => $dataExistCheck->id,
                    //                     'volume_rebate_percentage' => $value->volume_rebates,
                    //                     'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    //                     'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    //                 ]);
                    //             }
                    //         }
                    //     } else {
    
                    //     }
                    // } else {
                    //     if (!empty($totalAmount) && $totalAmount > 0) {
                    //         $newCommissionRebate = CommissionRebate::create([
                    //             'paid' => 0,
                    //             'approved' => 0,
                    //             'spend' => $totalAmount,
                    //             'quarter' => $filters['quarter'],
                    //             'end_date' => $filter['end_date'],
                    //             'sales_rep' => $values->sales_rep,
                    //             'start_date' => $filter['start_date'],
                    //             'volume_rebate' => $totalVolumeRebate,
                    //             'commissions' => $totalCommissionRebate,
                    //             'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    //             'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    //         ]);
            
                    //         foreach ($query->get() as $value) {
                    //             CommissionRebateDetail::create([
                    //                 'paid' => 0,
                    //                 'approved' => 0,
                    //                 'spend' => $value->cost,
                    //                 'month' =>  $filter['month'],
                    //                 'quarter' => $filters['quarter'],
                    //                 'end_date' => $filter['end_date'],
                    //                 'sales_rep' => $values->sales_rep,
                    //                 'supplier' => $value->supplier_id,
                    //                 'commissions' => $value->commissionss,
                    //                 'start_date' => $filter['start_date'],
                    //                 'account_name' => $value->account_name,
                    //                 'volume_rebate' => $value->volume_rebate,
                    //                 'commissions_end_date' => $value->end_date,
                    //                 'commissions_start_date' => $value->start_date,
                    //                 'commissions_percentage' => $value->commissions,
                    //                 'commissions_rebate_id' => $newCommissionRebate->id,
                    //                 'volume_rebate_percentage' => $value->volume_rebates,
                    //                 'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    //                 'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    //             ]);
                    //         }
                    //     }
                    // }
                }
            }
        }
    }   
}
