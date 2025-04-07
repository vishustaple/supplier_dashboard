<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RemoveFrontZeroAccountNumber extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:remove-front-zero-account-number';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(){
        /** Update for master_account_detail */
        DB::table('master_account_detail')
            ->where('account_number', 'LIKE', '0%')
            ->update(['account_number' => DB::raw("TRIM(LEADING '0' FROM `account_number`)")]);

        /** Select data from orders for update order_details table */
        $data = DB::table('orders')
            ->where('customer_number', 'LIKE', '0%')
            ->select('supplier_id', 'id', 'customer_number')->get();

        $columnArray = [
            1 => 1,
            2 => 453,
            3 => 48,
            4 => 491,
            5 => 125,
            6 => 394,
            7 => 203,
        ];

        foreach ($data as $key => $value) {
            DB::table('order_details')
            ->where('order_id', $value->id)
            ->where('supplier_field_id', $columnArray[$value->supplier_id])
            ->update(['value' => DB::raw("TRIM(LEADING '0' FROM `value`)")]);
        }

        /** Update for orders */
        DB::table('orders')
            ->where('customer_number', 'LIKE', '0%')
            ->update(['customer_number' => DB::raw("TRIM(LEADING '0' FROM `customer_number`)")]);

        /** Update for office_depot_order */
        DB::table('office_depot_order')
            ->where('customer_id', 'LIKE', '0%')
            ->update(['customer_id' => DB::raw("TRIM(LEADING '0' FROM `customer_id`)")]);

        /** Update for staples_order */
        DB::table('staples_order')
            ->where('master_customer_number_id', 'LIKE', '0%')
            ->update(['master_customer_number_id' => DB::raw("TRIM(LEADING '0' FROM `master_customer_number_id`)")]);

        /** Update for wb_mason_order */
        DB::table('wb_mason_order')
            ->where('customer_num', 'LIKE', '0%')
            ->update(['customer_num' => DB::raw("TRIM(LEADING '0' FROM `customer_num`)")]);

        /** Update for grainger_order */
        DB::table('grainger_order')
            ->where('account_number', 'LIKE', '0%')
            ->update(['account_number' => DB::raw("TRIM(LEADING '0' FROM `account_number`)")]);

        /** Update for g_and_t_laboratories_charles_river_order */
        DB::table('g_and_t_laboratories_charles_river_order')
            ->where('sold_toaccount', 'LIKE', '0%')
            ->update(['sold_toaccount' => DB::raw("TRIM(LEADING '0' FROM `sold_toaccount`)")]);
    }
}
