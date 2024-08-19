<?php

namespace App\Console\Commands;

use Illuminate\Support\Carbon;
use Illuminate\Console\Command;
use datetime;
use Illuminate\Support\Facades\DB;
use App\Models\{
    Order
};

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
        /** Supplier ids array */
        $supplier = [1 => 'Grand & Toy', 2 => 'Grainger', 3 => 'Office Depot', 4 => 'Staples', 5 => 'WB Mason', 6 => 'Lyreco'];

        /** Using foreach loop insert multiple supplier data */
        foreach ($supplier as $filter => $values) {
            /** Create a DateTime object from the original date */
            // $date = Order::selectRaw("DATE_FORMAT(date, '%Y-%m-%d') as formatted_date")
            $date = Order::selectRaw("date as formatted_date")
            ->where('supplier_id', $filter)
            ->orderBy('date', 'desc')
            ->limit(1)
            ->first();
            // $filter['supplier']
            // dd($date->toSql(), $date->getBindings());
            // dd($date);
            if (!isset($date) && empty($date)) {
                continue;
            } else {
                $originalDate = $end_date_2 = $end_date_10 = $end_date = $date->formatted_date;
            }
    
            /** Create a DateTime object from the original date */
            $date = new DateTime($originalDate);
    
            /** Subtract 52 weeks from the original date */
            $start_date_2 = (clone $date)->modify('-2 weeks')->format('Y-m-d');
            $start_date_10 = (clone $date)->modify('-10 weeks')->format('Y-m-d');
            $start_date = (clone $date)->modify('-52 weeks')->format('Y-m-d');
    
            $weeklyAmountsQuery = Order::query()
                ->join('master_account_detail as mad', 'orders.customer_number', '=', 'mad.account_number')
                ->join('suppliers', 'suppliers.id', '=', 'orders.supplier_id')
                ->selectRaw('YEAR(orders.date) as year, mad.account_name, orders.date, COALESCE(SUM(orders.amount), 0) as weekly_amount, suppliers.supplier_name as supplier_name, orders.supplier_id as supplier_id')
                ->whereBetween('orders.date', [$start_date, $end_date])
                ->groupBy(DB::raw('YEAR(orders.date)'), 'mad.account_name', 'orders.date', 'suppliers.supplier_name');
    
            $rankedAmountsQuery = Order::from(DB::raw("(".$weeklyAmountsQuery->toSql().") as WeeklyAmounts"))
                ->selectRaw("
                    year,
                    account_name,
                    supplier_name,
                    weekly_amount,
                    ROW_NUMBER() OVER (PARTITION BY year, account_name ORDER BY weekly_amount) as row_num,
                    COUNT(*) OVER (PARTITION BY year, account_name) as total_count
                ")
                ->whereBetween('date', [$start_date, $end_date])
                ->mergeBindings($weeklyAmountsQuery->getQuery()) ;
            $mediansQuery = Order::from(DB::raw("(".$rankedAmountsQuery->toSql().") as RankedAmounts"))
                ->selectRaw("
                    year,
                    account_name,
                    supplier_name,
                    AVG(weekly_amount) as median_52_weeks
                ")
                ->whereIn('row_num', [
                    DB::raw("FLOOR((total_count + 1) / 2)"), 
                    DB::raw("CEIL((total_count + 1) / 2)")
                ])
                ->groupBy('year', 'account_name', 'supplier_name')
                ->mergeBindings($rankedAmountsQuery->getQuery());
    
            $query = Order::from(DB::raw("(".$weeklyAmountsQuery->toSql().") as wa"))
                ->join('master_account_detail as mad', 'wa.account_name', '=', 'mad.account_name')
                ->join('suppliers', 'suppliers.id', '=', 'wa.supplier_id')
                ->join(DB::raw("(".$mediansQuery->toSql().") as m"), function($join) {
                    $join->on('m.year', '=', 'wa.year')
                        ->on('m.account_name', '=', 'wa.account_name');
                });
    
                // if ($csv) {
                    $query->selectRaw("
                    wa.year,
                    wa.account_name,
                    suppliers.supplier_name as supplier_name,
                    COALESCE(SUM(CASE WHEN wa.date BETWEEN ? AND ? THEN wa.weekly_amount ELSE 0 END) / 52, 0) as average_week_52,
                    COALESCE(SUM(CASE WHEN wa.date BETWEEN ? AND ? THEN wa.weekly_amount ELSE 0 END) / 10, 0) as average_week_10,
                    COALESCE(SUM(CASE WHEN wa.date BETWEEN ? AND ? THEN wa.weekly_amount ELSE 0 END) / 2, 0) as average_week_2,
                    FORMAT(
                        (
                            (SUM(CASE WHEN wa.date BETWEEN ? AND ? THEN wa.weekly_amount ELSE 0 END) / 52) -
                            (SUM(CASE WHEN wa.date BETWEEN ? AND ? THEN wa.weekly_amount ELSE 0 END) / 10) 
                        ) / 
                        (SUM(CASE WHEN wa.date BETWEEN ? AND ? THEN wa.weekly_amount ELSE 0 END) / 52) * 100, 2
                    ) as gap_percentage,
                    m.median_52_weeks
                    ", [
                        $start_date, $end_date,
                        $start_date_10, $end_date_10,
                        $start_date_2, $end_date_2,
                        $start_date, $end_date,
                        $start_date_10, $end_date_10,
                        $start_date, $end_date
                    ]);
                // } else {
                //     $query->selectRaw("
                //     wa.year,
                //     wa.account_name,
                //     suppliers.supplier_name as supplier_name,
                //     FORMAT(COALESCE(SUM(CASE WHEN wa.date BETWEEN ? AND ? THEN wa.weekly_amount ELSE 0 END) / 52, 0), 2) as average_week_52,
                //     FORMAT(COALESCE(SUM(CASE WHEN wa.date BETWEEN ? AND ? THEN wa.weekly_amount ELSE 0 END) / 10, 0), 2) as average_week_10,
                //     FORMAT(COALESCE(SUM(CASE WHEN wa.date BETWEEN ? AND ? THEN wa.weekly_amount ELSE 0 END) / 2, 0), 2) as average_week_2,
                //     FORMAT(
                //         (
                //             (SUM(CASE WHEN wa.date BETWEEN ? AND ? THEN wa.weekly_amount ELSE 0 END) / 52) -
                //             (SUM(CASE WHEN wa.date BETWEEN ? AND ? THEN wa.weekly_amount ELSE 0 END) / 10) 
                //         ) / 
                //         (SUM(CASE WHEN wa.date BETWEEN ? AND ? THEN wa.weekly_amount ELSE 0 END) / 52) * 100, 2
                //     ) as gap_percentage,
                //     m.median_52_weeks
                //     ", [
                //         $start_date, $end_date,
                //         $start_date_10, $end_date_10,
                //         $start_date_2, $end_date_2,
                //         $start_date, $end_date,
                //         $start_date_10, $end_date_10,
                //         $start_date, $end_date
                //     ]);
                // }
                
                $query->groupBy('wa.year', 'wa.account_name', 'suppliers.supplier_name')
                ->havingRaw('CAST(average_week_52 AS DECIMAL(10, 2)) > CAST(average_week_10 AS DECIMAL(10, 2))')
                ->havingRaw('CAST(average_week_10 AS DECIMAL(10, 2)) != 0.00')
                ->mergeBindings($weeklyAmountsQuery->getQuery())
                ->mergeBindings($mediansQuery->getQuery());
    
            // $totalRecords = 0;
            // $totalRecords = $query->getQuery()->getCountForPagination();
    
            /** Getting the query data using method get */
            $queryData = $query->get();

            $finalArray = [];
            foreach ($queryData as $key => $value) {
                if ($value->gap_percentage >= 20 && ($value->supplier_name == $values)) {
                    /** Prepare the final array for non-CSV */
                    $finalArray[] = [
                        'account_name' => $value->account_name,
                        'supplier_name' => $value->supplier_name,
                        'fifty_two_wk_avg' => $value->average_week_52,
                        'ten_week_avg' => $value->average_week_10,
                        'two_wk_avg_percentage' => $value->average_week_2,
                        'drop' => $value->gap_percentage,
                        'median' => $value->median_52_weeks,
                        'date' => $originalDate,
                        'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                        'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    ];
                }
            }
    
            DB::table('operational_anomaly_report')->insert($finalArray);
        }
    }
}
