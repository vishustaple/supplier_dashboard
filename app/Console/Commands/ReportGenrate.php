<?php

namespace App\Console\Commands;

use datetime;
use App\Models\Order;
use Illuminate\Support\Carbon;
use Illuminate\Console\Command;
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
        // $count = DB::table('operational_anomaly_report')->count();

        // if ($count <= 0) {
            /** Supplier ids array */
            $supplier = [1 => 'Grand & Toy', 2 => 'Grainger', 3 => 'Office Depot', 4 => 'Staples', 5 => 'WB Mason', 6 => 'Lyreco'];

            /** Using foreach loop insert multiple supplier data */
            foreach ($supplier as $filter => $values) {
                /** Create a DateTime object from the original date */
                $date = Order::selectRaw("date as formatted_date")
                ->where('supplier_id', $filter)
                ->orderBy('date', 'desc')
                ->limit(1)
                ->first();
                
                // dd($date->toSql(), $date->getBindings());
                // dd($date);

                if (!isset($date) && empty($date)) {
                    continue;
                } else {
                    $date = $date->formatted_date;
                }

                /** Convert the date to a DateTime object */
                $dateTime = new DateTime($date);

                /** Check if the given date is a Sunday */
                if ($dateTime->format('w') != 0) {
                    /** If not Sunday, modify the date to the previous Sunday */
                    $dateTime->modify('last Sunday');
                }

                /** Selecting end date */
                $originalDate = $end_date_2 = $end_date_10 = $end_date = $dateTime->format('Y-m-d'); /** The selected Sunday */

                /** Now, calculate the date two weeks before the selected Sunday */
                $dateTime->modify('-2 weeks');
                $start_date_2 = $dateTime->format('Y-m-d'); /** Date two weeks before */

                /** Calculate the date 10 weeks before the selected Sunday */
                $dateTime->modify('-8 weeks'); /** Already moved 2 weeks back, so move 8 more weeks */
                $start_date_10 = $dateTime->format('Y-m-d'); /** Date ten weeks before */

                /** Calculate the date 52 weeks before the selected Sunday */
                $dateTime->modify('-42 weeks'); /** Already moved 10 weeks back, so move 42 more weeks */
                $start_date = $dateTime->format('Y-m-d'); /** Date fifty-two weeks before */

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
                    
                $query->groupBy('wa.year', 'wa.account_name', 'suppliers.supplier_name')
                    ->havingRaw('CAST(average_week_52 AS DECIMAL(10, 2)) > CAST(average_week_10 AS DECIMAL(10, 2))')
                    ->havingRaw('CAST(average_week_10 AS DECIMAL(10, 2)) != 0.00')
                    ->mergeBindings($weeklyAmountsQuery->getQuery())
                    ->mergeBindings($mediansQuery->getQuery());
        
                /** Getting the query data using method get */
                $queryData = $query->get();

                // /** WeeklyAmounts subquery */
                // $weeklyAmounts = Order::query()
                // ->selectRaw('
                //     mad.account_name,
                //     s.supplier_name,
                //     orders.supplier_id,
                //     YEAR(orders.date) * 100 + WEEK(orders.date) AS YYWW,
                //     COALESCE(SUM(orders.amount), 0) AS weekly_amount
                // ')
                // ->leftJoin('master_account_detail as mad', 'orders.customer_number', '=', 'mad.account_number')
                // ->leftJoin('suppliers as s', 'orders.supplier_id', '=', 's.id')
                // ->whereBetween('orders.date', [$start_date, $end_date])
                // ->groupBy('mad.account_name', 's.supplier_name', 'orders.supplier_id', 'YYWW');

                // /** Medians subquery */
                // $medians = Order::from(DB::raw("({$weeklyAmounts->toSql()}) as wa"))
                // ->mergeBindings($weeklyAmounts->getQuery()) /** Merge bindings from the first query */
                // ->selectRaw('
                //     wa.account_name,
                //     wa.supplier_name,
                //     wa.supplier_id,
                //     SUBSTRING_INDEX(
                //         SUBSTRING_INDEX(
                //             GROUP_CONCAT(wa.weekly_amount ORDER BY wa.weekly_amount ASC), 
                //             \',\', 
                //             (COUNT(*) + 1) / 2
                //         ), 
                //         \',\', 
                //         -1
                //     ) AS median_52_weeks
                // ')
                // ->groupBy('wa.account_name', 'wa.supplier_name', 'wa.supplier_id');

                // /** Averages subquery */
                // $averages = Order::from(DB::raw("({$weeklyAmounts->toSql()}) as wa"))
                // ->mergeBindings($weeklyAmounts->getQuery()) /** Merge bindings from the first query */
                // ->selectRaw('
                //     wa.account_name,
                //     wa.supplier_name,
                //     wa.supplier_id,
                //     AVG(CASE WHEN wa.YYWW BETWEEN (YEAR(?) * 100 + WEEK(?)) 
                //                                 AND (YEAR(?) * 100 + WEEK(?)) 
                //             THEN wa.weekly_amount ELSE NULL END) AS avg_52_weeks,
                //     AVG(CASE WHEN wa.YYWW BETWEEN (YEAR(?) * 100 + WEEK(?)) 
                //                                 AND (YEAR(?) * 100 + WEEK(?)) 
                //             THEN wa.weekly_amount ELSE NULL END) AS avg_10_weeks,
                //     AVG(CASE WHEN wa.YYWW BETWEEN (YEAR(?) * 100 + WEEK(?)) 
                //                                 AND (YEAR(?) * 100 + WEEK(?)) 
                //             THEN wa.weekly_amount ELSE NULL END) AS avg_2_weeks
                // ', [$start_date, $start_date, $end_date, $end_date,
                //     $start_date_10, $start_date_10, $end_date_10, $end_date_10,
                //     $start_date_2, $start_date_2, $end_date_2, $end_date_2])
                // ->groupBy('wa.account_name', 'wa.supplier_name', 'wa.supplier_id');

                // /** Final query */
                // $queryData = Order::from(DB::raw("({$averages->toSql()}) as a"))
                // ->mergeBindings($averages->getQuery()) /** Merge bindings from the averages query */
                // ->mergeBindings($medians->getQuery()) /** Merge bindings from the medians query */
                // ->leftJoin(DB::raw("({$medians->toSql()}) as m"), function ($join) {
                //     $join->on('a.account_name', '=', 'm.account_name')
                //         ->on('a.supplier_id', '=', 'm.supplier_id');
                // })
                // ->selectRaw('
                //     a.account_name,
                //     a.supplier_name,
                //     a.supplier_id,
                //     a.avg_52_weeks,
                //     a.avg_10_weeks,
                //     a.avg_2_weeks,
                //     m.median_52_weeks,
                //     ROUND(((m.median_52_weeks - a.avg_2_weeks) / m.median_52_weeks) * 100, 2) AS percentage_drop
                // ')
                // // ->having('a.avg_52_weeks', '<', DB::raw('a.avg_2_weeks'))
                // ->get();

                $finalArray = [];
                // foreach ($queryData as $key => $value) {
                //     // if ($value->percentage_drop >= 20 && ($value->supplier_name == $values)) {
                //         /** Prepare the final array for non-CSV */
                //         $finalArray[] = [
                //             'account_name' => $value->account_name,
                //             'supplier_name' => $value->supplier_name,
                //             'fifty_two_wk_avg' => $value->avg_52_weeks,
                //             'ten_week_avg' => $value->avg_10_weeks,
                //             'two_wk_avg_percentage' => $value->avg_2_weeks,
                //             'drop' => $value->percentage_drop,
                //             'median' => $value->median_52_weeks,
                //             'date' => $originalDate,
                //             'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                //             'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                //         ];
                //     // }
                // }

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

                print_r($finalArray);
                DB::table('operational_anomaly_report')->insert($finalArray);
            }
        // }
    }
}
