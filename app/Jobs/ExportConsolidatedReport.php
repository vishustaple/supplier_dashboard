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
    public $filePath;

    /**
     * Create a new job instance.
     */
    public function __construct($filter, $userId)
    {
        $this->filter = $filter;
        $this->userId = $userId;
        $this->filePath = 'Consolidated_Report_' . now()->format('YmdHis') . '.csv';
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $userFileExist = DB::table('consolidated_file')
        ->select('user_id', 'file_name')
        ->where('user_id', $this->userId)
        ->first();

        if ($userFileExist) {
            if (File::exists('app/' . $userFileExist->file_name)) {
                try {
                    File::delete('app/' . $userFileExist->file_name);
                    DB::table('consolidated_file')
                        ->where('user_id', $userFileExist->user_id)
                        ->delete();
                } catch (\Exception $e) {
                    /** Log or handle the error */
                    Log::error('Error deleting file: ' . $e->getMessage());
                    session()->flash('error', 'Error deleting file: ' . $e->getMessage());
                }
            }
        }

        Log::info('Queue started for exporting consolidated report.');
        /** Open a writable stream in storage */
        $stream = fopen(storage_path('app/' . $this->filePath), 'w');
        $csvWriter = Writer::createFromStream($stream);

        /** Fetch data in chunks to avoid memory overload */
        $data = Order::getConsolidatedDownloadData($this->filter);

        /** Create a new CSV writer instance */
        $csvWriter = Writer::createFromStream($stream);
        
        $previousKeys = [];
 
        /** Loop through data */
        foreach ($data as $row) {
            $currentKeys = array_keys($row);
 
            /** Check if the keys have changed */
            if ($currentKeys !== $previousKeys) {
                /** If keys have changed, insert the new heading row */
                $csvWriter->insertOne($currentKeys);
                $previousKeys = $currentKeys;
            }

            /** Reorder the current row according to the current keys */
            $orderedRow = [];
            foreach ($currentKeys as $key) {
                $orderedRow[] = $row[$key] ?? '';
            }
 
            /** Insert the data row */
            $csvWriter->insertOne($orderedRow);
        }

        Log::info('Export completed. CSV file created at: ');

        fclose($stream);

        /** Notify the user after file creation */
        DB::table('consolidated_file')
        ->insert([
            'user_id' => $this->userId,
            'file_name' => $this->filePath,
            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);
    }
}
