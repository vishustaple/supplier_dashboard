<?php

namespace App\Jobs;

use App\Models\Order;
use App\Notifications\ExportReadyNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use League\Csv\Writer;
use Illuminate\Queue\{
    SerializesModels,
    InteractsWithQueue,
};
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\{
    ShouldQueue,
    ShouldBeUnique,
};
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExportConsolidatedReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $filter;
    public $userId;
    public $fileId;
    public $filePath;

    /**
     * Create a new job instance.
     */
    public function __construct($filter, $userId, $fileId)
    {
        $this->filter = $filter;
        $this->userId = $userId;
        $this->fileId = $fileId;
        $this->filePath = 'Consolidated_Report_' . now()->format('YmdHis') . '.csv';
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {    
        Log::info('Queue started for exporting consolidated report.');

        /** Fetch data in chunks to avoid memory overload */
        $fileCreatedCheck = Order::getConsolidatedDownloadDataSecond($this->filter, $this->filePath);

        Log::info('Export completed. CSV file created at: ');

        if ($fileCreatedCheck) {
            /** Notify the user after file creation */
            DB::table('consolidated_file')
            ->where('id', $this->fileId)
            ->update([
                'file_name' => $this->filePath,
                'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);
        }
    }
}
