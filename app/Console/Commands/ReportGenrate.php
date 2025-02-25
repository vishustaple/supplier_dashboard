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
    public function handle() {
        /** Getting the count of "operational_anomaly_report" table */
        $count = DB::table('operational_anomaly_report')->count();

        /** Checking the count if it is "Zero"
         *  It means new file inserted by the user 
         *  We need to re-calculate
         *  Insert data into "operational_anomaly_report" table */
            if ($count <= 0) {
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

                /** If date is empty then 
                 * we need to move next supplier data 
                 * using loop "continue" */
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

                /** Weekly Amounts Aubquery */
                $weeklyAmounts = Order::query()
                ->selectRaw('
                    mad.account_name AS account_name,
                    s.supplier_name AS supplier_name,
                    orders.supplier_id,
                    YEAR(orders.date) AS year,
                    orders.date AS order_date,
                    YEAR(orders.date) * 100 + WEEK(orders.date) AS YYWW,
                    COALESCE(SUM(orders.cost), 0) AS weekly_amount
                ')
                ->leftJoin('master_account_detail as mad', 'orders.customer_number', '=', 'mad.account_number')
                ->leftJoin('suppliers as s', 'orders.supplier_id', '=', 's.id')
                ->whereBetween('orders.date', [$start_date, $end_date])
                ->groupBy('mad.account_name', 's.supplier_name', 'orders.supplier_id', 'YYWW');

                /** Ranked Amounts Subquery */
                $rankedAmountsQuery = Order::from(DB::raw("(".$weeklyAmounts->toSql().") as ra"))
                ->selectRaw("
                    ra.year,
                    ra.account_name,
                    ra.supplier_name,
                    ra.supplier_id,
                    ra.weekly_amount,
                    ROW_NUMBER() OVER (PARTITION BY ra.year, ra.account_name ORDER BY ra.weekly_amount) as row_num,
                    COUNT(*) OVER (PARTITION BY ra.year, ra.account_name) as total_count
                ")
                ->whereBetween('ra.order_date', [$start_date, $end_date])
                ->mergeBindings($weeklyAmounts->getQuery()) ;
                
                /** Medians Subquery */
                $medians = Order::from(DB::raw("({$rankedAmountsQuery->toSql()}) as ma"))
                ->mergeBindings($rankedAmountsQuery->getQuery()) /** Merge bindings from the first query */
                ->selectRaw("
                    ma.account_name,
                    ma.supplier_name,
                    ma.supplier_id,
                    AVG(ma.weekly_amount) as median_52_weeks
                ")
                ->whereIn('row_num', [
                    DB::raw("FLOOR((ma.total_count + 1) / 2)"), 
                    DB::raw("CEIL((ma.total_count + 1) / 2)")
                ])
                ->groupBy('ma.account_name', 'ma.supplier_name', 'ma.supplier_id');

                /** Averages Subquery */
                $averages = Order::from(DB::raw("({$weeklyAmounts->toSql()}) as wa"))
                ->mergeBindings($weeklyAmounts->getQuery()) /** Merge bindings from the first query */
                ->selectRaw('
                    wa.account_name,
                    wa.supplier_name,
                    wa.supplier_id,
                    SUM(CASE WHEN wa.order_date BETWEEN ? 
                                                AND ? 
                            THEN wa.weekly_amount ELSE 0 END) / 52 as avg_52_weeks,
                    SUM(CASE WHEN wa.order_date BETWEEN ? 
                                                AND ? 
                            THEN wa.weekly_amount ELSE 0 END) / 10 as avg_10_weeks,

                    SUM(CASE WHEN wa.order_date BETWEEN ? 
                                        AND ? 
                            THEN wa.weekly_amount ELSE 0 END) / 2 as avg_2_weeks
                ', [$start_date, $end_date, $start_date_10, $end_date_10,
                    $start_date_2, $end_date_2])
                ->groupBy('wa.account_name', 'wa.supplier_name', 'wa.supplier_id');
                
                /** Final query */
                $queryData = Order::from(DB::raw("({$averages->toSql()}) as a"))
                ->mergeBindings($averages->getQuery()) /** Merge bindings from the averages query */
                ->leftJoin(DB::raw("({$medians->toSql()}) as m"), function ($join) {
                    $join->on('a.account_name', '=', 'm.account_name')
                    ->on('a.supplier_id', '=', 'm.supplier_id');
                })
                ->mergeBindings($medians->getQuery()) /** Merge bindings from the medians query */
                ->selectRaw('
                    a.account_name,
                    a.supplier_name,
                    a.supplier_id,
                    a.avg_52_weeks,
                    a.avg_10_weeks,
                    a.avg_2_weeks,
                    m.median_52_weeks,
                    ROUND(((m.median_52_weeks - a.avg_2_weeks) / m.median_52_weeks) * 100, 2) AS percentage_drop
                ')
                ->get();

                $finalArray = [];
                foreach ($queryData as $key => $value) {
                    if ($value->percentage_drop >= 20 && $value->supplier_name == $values && $value->avg_52_weeks >= 500) {
                        /** Prepare the final array for non-CSV */
                        $finalArray[] = [
                            'account_name' => $value->account_name,
                            'supplier_name' => $value->supplier_name,
                            'fifty_two_wk_avg' => $value->avg_52_weeks,
                            'ten_week_avg' => $value->avg_10_weeks,
                            'two_wk_avg_percentage' => $value->avg_2_weeks,
                            'drop' => $value->percentage_drop,
                            'median' => $value->median_52_weeks,
                            'date' => $originalDate,
                            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                            'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                        ];
                    }
                }

                /** Finaly inserting the report data into the "operational_anomaly_report" table */
                DB::table('operational_anomaly_report')->insert($finalArray);
            }
        }
    }
}