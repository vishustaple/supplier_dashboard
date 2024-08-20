<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendReportEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-report-email';

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
        try{
            Log::info('Attempting to send email...');
            $supplier = [1 => 'Grand & Toy', 2 => 'Grainger', 3 => 'Office Depot', 4 => 'Staples', 5 => 'WB Mason', 6 => 'Lyreco'];
            $supplierDate = ''; 
            foreach ($supplier as $key => $value) {
                $date = Order::selectRaw("DATE_FORMAT(date, '%Y-%m-%d') as formatted_date")
                ->where('supplier_id', $key)
                ->orderBy('date', 'desc')
                ->limit(1)
                ->first();
                if ($date) {
                    $supplierDate .= '<p class="card-text"><b>'.$value.': </b> '.$date->formatted_date.'</p>';
                }
            }
            
            $data = DB::table("operational_anomaly_report")
            ->selectRaw("
                account_name,
                supplier_name,
                FORMAT(fifty_two_wk_avg, 2) AS fifty_two_wk_avg,
                FORMAT(ten_week_avg, 2) AS ten_week_avg,
                FORMAT(two_wk_avg_percentage, 2) AS two_wk_avg_percentage,
                FORMAT(`drop`, 2) AS `drop`,
                FORMAT(median, 2) AS median
            ")
            ->get();
            
            $email = 'mgaballa@centerpointgroup.com';
            $rr = Mail::send('mail.operationalanomalyreport', ['data' => $data, 'supplierDate' => $supplierDate], function($message) use ($email) {
                $message->to($email)
                ->subject('Operational Anomaly Report');
            });
            dd($rr);
            Log::info('Email sent successfully');
        } catch (\Exception $e) {
            dd($e->getMessage());
            /** Handle the exception here */
            Log::error('Email sending failed: ' . $e->getMessage());
        }
    }
}
