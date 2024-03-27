<?php

namespace App\Console\Commands;

use DB;
use Illuminate\Support\Carbon;
use Illuminate\Console\Command;
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
        $salesRep = SalesTeam::select('id as sales_rep')->get();
        $filter1['year']=2023;
        $fys = ['CALENDAR' => $filter1['year'].'-01-01'];
        foreach ($fys as $key => $start){
            $nextYear = $filter1['year']+1;
            $res["1"] = date('Y-m-d H:i:s', strtotime($filter1['year'].'-01-01'));
            $res["2"] = date('Y-m-d H:i:s', strtotime($filter1['year'].'-03-31'));
            $res["3"] = date('Y-m-d H:i:s', strtotime($filter1['year'].'-04-01'));
            $res["4"] = date('Y-m-d H:i:s', strtotime($filter1['year'].'-07-31'));
            $res["5"] = date('Y-m-d H:i:s', strtotime($nextYear.'-01-01'));
            $dateArray[$key] = $res;
        }
        
        $filters = [
            0 => ['quarter' => 'Quarter 1'],
            1 => ['quarter' => 'Quarter 2'],
            2 => ['quarter' => 'Quarter 3'],
            3 => ['quarter' => 'Quarter 4'],
        ];
        
        foreach ($salesRep as $key => $values) {
            foreach ($filters as $key => $filter) {
                $query = Order::query()->selectRaw("SUM(`orders`.`amount`) AS `amount`, 
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
                `orders`.`date` AS `date`")
                ->leftJoin('master_account_detail as m2', 'orders.customer_number', '=', 'm2.account_number')
                ->leftJoin('suppliers', 'suppliers.id', '=', 'orders.supplier_id')
                ->leftJoin('rebate', 'rebate.account_name', '=', 'm2.account_name')
                ->leftJoin('commission', function ($join) { $join->on('commission.supplier', '=', 'suppliers.id')->on('commission.account_name', '=', 'm2.account_name'); });
    
                $query->where('commission.sales_rep', $values->sales_rep);
    
                $query->whereIn('commission.supplier', [1,2,3,4,5,6,7]);      
            
                /** Year and quarter filter here */
                if($filter['quarter'] == 'Quarter 1'){
                    $startDate =  $dateArray['CALENDAR']["1"];
                    $endDate =  $dateArray['CALENDAR']["2"];
                }
        
                if($filter['quarter'] == 'Quarter 2'){
                    $startDate=  $dateArray['CALENDAR']["2"];
                    $endDate=  $dateArray['CALENDAR']["3"];
                }
        
                if($filter['quarter'] == 'Quarter 3'){
                    $startDate=  $dateArray['CALENDAR']["3"];
                    $endDate=  $dateArray['CALENDAR']["4"];
                }
        
                if($filter['quarter'] == 'Quarter 4'){
                    $startDate=  $dateArray['CALENDAR']["4"];
                    $endDate=  $dateArray['CALENDAR']["5"];
                }
        
                if ($filter['quarter'] == 'Annual'){
                    $startDate=  $dateArray['CALENDAR']["1"];
                    $endDate=  $dateArray['CALENDAR']["5"];
                }
                $query->whereBetween('orders.date', [$startDate, $endDate])
                ->where('commission.start_date', '<=', DB::raw('orders.date'))
                ->where('commission.end_date', '>=', DB::raw('orders.date'));
            
                /** Group by with account name */
                $query->groupBy('m2.account_name');
    
                /** Calculating total volume rebate, total commission on rebate and total amount */
                $totalAmount = $totalVolumeRebate = $totalCommissionRebate = 0;
                foreach ($query->get() as $key => $value) {
                    $totalVolumeRebate += $value->volume_rebate;
                    $totalCommissionRebate += $value->commissions;
                    $totalAmount += $value->amount;
                }

                if ($totalVolumeRebate > 0) {
                    $volumeRebatePercentage = ($totalVolumeRebate / $totalAmount) * 100;
                } else {
                    $volumeRebatePercentage = 0;
                }

                if ($totalCommissionRebate > 0) {
                    $commissionRebatePercentage = ($totalCommissionRebate / $totalVolumeRebate) * 100;
                } else {
                    $commissionRebatePercentage = 0;
                }

                // print_r($volumeRebatePercentage);
                // echo"                          ";
                // print_r($commissionRebatePercentage);
                $newCommissionRebate = CommissionRebate::create([
                    'sales_rep' => $values->sales_rep,
                    'commission' => $totalCommissionRebate,
                    'volume_rebate' => $totalVolumeRebate,
                    'spend' => $totalAmount,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'approved' => 0,
                    'paid' => 0,
                    // 'commission_percentage' => $commissionRebatePercentage,
                    // 'volume_rebate_percentage' => $volumeRebatePercentage,
                    'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                ]);
                foreach ($query->get() as $key => $value) {
                    CommissionRebateDetail::create([
                        'commission_rebate_id' => $newCommissionRebate->id,
                        'commission' => $value->commissions,
                        'volume_rebate' => $value->volume_rebate,
                        'spend' => $value->amount,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'approved' => 0,
                        'paid' => 0,
                        'supplier' => $value->supplier_id,
                        'commission_percentage' => $value->commission,
                        'volume_rebate_percentage' => $value->volume_rebates,
                        'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                        'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    ]);
                }
            }
        }
    }   
}
