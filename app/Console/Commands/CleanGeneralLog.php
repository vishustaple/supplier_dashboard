<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanGeneralLog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clean-general-log';

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
        try {
            // Step 1: Insert only UPDATE and DELETE queries into your custom log table
            DB::statement("
                INSERT INTO custom_query_log (event_time, user_host, argument, query_type, created_at)
                SELECT 
                    event_time,
                    user_host,
                    argument,
                    CASE
                        WHEN argument REGEXP '^UPDATE[[:space:]]' THEN 'UPDATE'
                        WHEN argument REGEXP '^DELETE[[:space:]]' THEN 'DELETE'
                    END as query_type,
                    NOW()
                FROM mysql.general_log
                WHERE command_type = 'Query'
                  AND (argument REGEXP '^UPDATE[[:space:]]' OR argument REGEXP '^DELETE[[:space:]]')
            ");
    
            $this->info('Archived UPDATE and DELETE queries to custom_query_log.');
    
            // Step 2: Disable general logging temporarily
            DB::statement("SET GLOBAL general_log = 'OFF'");
    
            // Step 3: Truncate the log table (clears all entries)
            DB::statement("TRUNCATE TABLE mysql.general_log");
    
            DB::statement("SET GLOBAL log_output = 'TABLE'");

            // Step 4: Re-enable logging
            DB::statement("SET GLOBAL general_log = 'ON'");
            
            // INSERT INTO sqldb2.users SELECT * FROM sqldb1.users;
            // INSERT INTO sqldb2.suppliers SELECT * FROM sqldb1.suppliers;
            $this->info('Truncated mysql.general_log and re-enabled logging.');
        } catch (\Exception $e) {
            $this->error('Error processing general_log: ' . $e->getMessage());
        }
    }
}
