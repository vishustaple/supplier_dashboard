<?php

namespace App\Console\Commands;

use Illuminate\Support\Carbon;
use Illuminate\Console\Command;
use datetime;
use Illuminate\Support\Facades\DB;

class ReportGenrate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:report-genrate';

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
        $originalDate = '2024-08-13';
        // Create a DateTime object from the original date
        $date = new DateTime($originalDate);

        // Subtract 52 weeks from the original date
        $date->modify('-2 weeks');

        // Format the date to your desired format
        $previousDate = $date->format('Y-m-d');
        dd($previousDate);

        DB::table('operational_anomaly_report')->delete();
        
        $weeklyAmounts = DB::table('orders')
        ->selectRaw('YEAR(orders.date) AS year')
        ->selectRaw('mad.account_name')
        ->selectRaw('WEEKOFYEAR(orders.date) AS week_number')
        ->selectRaw('suppliers.supplier_name as supplier_name')
        ->selectRaw('COALESCE(SUM(orders.amount), 0) AS weekly_amount')
        ->join('suppliers', 'orders.supplier_id', '=', 'suppliers.id')
        ->join('master_account_detail AS mad', 'orders.customer_number', '=', 'mad.account_number')
        ->groupBy(DB::raw('YEAR(orders.date), mad.account_name, suppliers.supplier_name, WEEKOFYEAR(orders.date)'));
        
        $rankedWeeklyAmounts = DB::table(DB::raw('(' . $weeklyAmounts->toSql() . ') as WeeklyAmounts'))
        ->select('year', 'account_name', 'supplier_name', 'weekly_amount', 'week_number')
        ->selectRaw('ROW_NUMBER() OVER (PARTITION BY year, account_name ORDER BY weekly_amount) AS row_num')
        ->selectRaw('COUNT(*) OVER (PARTITION BY year, account_name, supplier_name) AS total_weeks');
            
        $medians = DB::table(DB::raw('(' . $rankedWeeklyAmounts->toSql() . ') as RankedWeeklyAmounts'))
        ->select('year', 'account_name', 'supplier_name')
        ->selectRaw('AVG(weekly_amount) AS median_52_weeks')
        ->whereRaw('row_num IN (FLOOR((total_weeks + 1) / 2), CEIL((total_weeks + 1) / 2))')
        ->groupBy('year', 'account_name', 'supplier_name');
        
        $queryData = DB::table('orders')
        ->selectRaw('YEAR(orders.date) AS year')
        ->selectRaw('mad.account_name')
        ->selectRaw('suppliers.supplier_name as supplier_name')
        ->selectRaw('CASE
                WHEN MAX(WEEKOFYEAR(orders.date)) = 52 THEN
                    SUM(COALESCE(orders.amount, 0)) / 52
                ELSE
                    SUM(COALESCE(orders.amount, 0)) / 53
                END AS average_week_52')
        ->selectRaw('SUM(CASE WHEN WEEKOFYEAR(orders.date) <= 10 THEN COALESCE(orders.amount, 0) ELSE 0 END) / 10 AS average_week_10')
        ->selectRaw('SUM(CASE WHEN WEEKOFYEAR(orders.date) <= 2 THEN COALESCE(orders.amount, 0) ELSE 0 END) / 2 AS average_week_2')
        ->selectRaw('CASE
            WHEN MAX(WEEKOFYEAR(orders.date)) = 52 THEN
                CASE
                    WHEN SUM(CASE WHEN WEEKOFYEAR(orders.date) = 52 THEN COALESCE(orders.amount, 0) ELSE 0 END) <> 0 THEN
                        ABS(
                            (SUM(CASE WHEN WEEKOFYEAR(orders.date) <= 10 THEN COALESCE(orders.amount, 0) ELSE 0 END) / 10) -
                            (SUM(COALESCE(orders.amount, 0)) / 52)
                        ) / (SUM(COALESCE(orders.amount, 0)) / 52) * 100
                    ELSE
                        NULL
                END
            WHEN MAX(WEEKOFYEAR(orders.date)) = 53 THEN
                CASE
                    WHEN SUM(CASE WHEN WEEKOFYEAR(orders.date) = 53 THEN COALESCE(orders.amount, 0) ELSE 0 END) <> 0 THEN
                        ABS(
                            (SUM(CASE WHEN WEEKOFYEAR(orders.date) <= 10 THEN COALESCE(orders.amount, 0) ELSE 0 END) / 10) -
                            (SUM(COALESCE(orders.amount, 0)) / 53)
                        ) / (SUM(COALESCE(orders.amount, 0)) / 53) * 100
                    ELSE
                        NULL
                END
            ELSE
                NULL
            END AS gap_percentage')
        ->selectRaw('m.median_52_weeks')
        ->join('master_account_detail AS mad', 'orders.customer_number', '=', 'mad.account_number')
        ->join('suppliers', 'orders.supplier_id', '=', 'suppliers.id')
        ->join(DB::raw('(' . $medians->toSql() . ') as m'), function($join) {
            $join->on('m.year', '=', DB::raw('YEAR(orders.date)'))
                    ->on('m.account_name', '=', 'mad.account_name');
        })
        ->groupBy(DB::raw('YEAR(orders.date), mad.account_name'))
        ->havingRaw('CAST(gap_percentage AS DECIMAL(10, 2)) > 20')
        ->get();

        $finalArray = [];
        foreach ($queryData as $key => $value) {
            /** Prepare the final array for non-CSV */
            $finalArray[] = [
                'account_name' => $value->account_name,
                'supplier_name' => $value->supplier_name,
                'fifty_two_wk_avg' => $value->average_week_52,
                'ten_week_avg' => $value->average_week_10,
                'two_wk_avg_percentage' => $value->average_week_2,
                'drop' => $value->gap_percentage,
                'median' => $value->median_52_weeks,
                'year' => $value->year,
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ];
        }

        DB::table('operational_anomaly_report')->insert($finalArray);
    }
}
