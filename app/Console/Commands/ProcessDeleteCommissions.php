<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessDeleteCommissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-delete-commissions';

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
        /** Selecting the sales_rep from commissions table id from sales_team table using left join */
        $salesRep = DB::table('commissions')
        ->select(
            'commissions.sales_rep as id',
            'sales_team.id as sales_rep'
        )
        ->leftJoin(
            'sales_team',
            'sales_team.id',
            '=',
            'commissions.sales_rep'
        )
        ->get();

        foreach($salesRep as $key => $value){
            if (empty($value->sales_rep)) {
                /** Delete records from Commission table */
                DB::table('commissions')->where('sales_rep', $value->id)->delete();

                /** Delete records from Commission Rebate Detail table */
                DB::table('commissions_rebate_detail')->where('sales_rep', $value->id)->delete();

                /** Delete records from Commission Rebate table */
                DB::table('commissions_rebate')->where('sales_rep', $value->id)->delete();
            }
        }
    }
}
