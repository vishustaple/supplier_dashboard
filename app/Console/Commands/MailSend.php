<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Models\{UploadedFiles, CategorySupplier};

class MailSend extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:mail-send';

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
        $supplierId = ['1','2','3','4','5','6'];

        foreach($supplierId as $id){
            if($id == 5){
                if (Carbon::now()->day <= 15) {
                    /** First half of the month */
                    $startDate = Carbon::now()->startOfMonth();
                    $endDate = Carbon::now()->startOfMonth()->addDays(14)->endOfDay();
                
                    if(!empty($startDate) && !empty($endDate)){
                        $data = UploadedFiles::where('supplier_id',$id)->whereBetween('created_at', [$startDate, $endDate])->latest('created_at')
                        ->first();
                    
                        if(!isset($data)){
                            $this->info('no issue first half ' . $id);

                            $supplierName = CategorySupplier::where('id',$id)->value('supplier_name');
                            $email = 'vishustaple.in@gmail.com';

                            $this->sendEmail($email, $supplierName);
                        }
                    }
                } else {
                    /** Second half of the month */
                    $startDate = Carbon::now()->startOfMonth()->addDays(15);
                    $endDate = Carbon::now()->endOfMonth();
                    if(!empty($startDate) && !empty($endDate)){
                            
                        $data = UploadedFiles::where('supplier_id', $id)->whereBetween('created_at', [$startDate, $endDate])->latest('created_at')
                        ->first();
                        if(!isset($data)){
                            $this->info('no issue second half ' . $id);
                        
                                $supplierName = CategorySupplier::where('id',$id)->value('supplier_name');
                                $email = 'vishustaple.in@gmail.com'; 
                                $this->sendEmail($email, $supplierName);
                            }
                    }
                }
            }

            if($id == 1 || $id == 2 || $id == 3|| $id == 4){
                if($id == 4){
                    $currentDay = Carbon::now()->day;
                    // dd($currentDay);
                    $currentWeek = ceil($currentDay / 7); 
                    
                    if ($currentWeek % 2 == 1) {
                        /** Odd week */
                        $startDate = Carbon::now()->startOfWeek();
                        $endDate = Carbon::now()->endOfWeek();
                
                        if (!empty($startDate) && !empty($endDate)) {
                            $data = UploadedFiles::where('supplier_id', $id)
                                ->whereBetween('created_at', [$startDate, $endDate])
                                ->latest('created_at')
                                ->first();
                
                            if (!isset($data)) {
                                $this->info('no issue in odd week ' . $id);
                
                                $supplierName = CategorySupplier::where('id', $id)->value('supplier_name');
                                $email = 'vishustaple.in@gmail.com';

                                $this->sendEmail($email, $supplierName);
                            }
                        }
                    } else {

                        /** Even week */
                        $startDate = Carbon::now()->startOfWeek()->addDays(7);
                        $endDate = Carbon::now()->endOfWeek()->addDays(7);
                
                        if (!empty($startDate) && !empty($endDate)) {
                            $data = UploadedFiles::where('supplier_id', $id)
                                ->whereBetween('created_at', [$startDate, $endDate])
                                ->latest('created_at')
                                ->first();
                
                            if (!isset($data)) {
                                $this->info('no issue in even week ' . $id);
                
                                $supplierName = CategorySupplier::where('id', $id)->value('supplier_name');
                                $email = 'vishustaple.in@gmail.com';
                                $this->sendEmail($email, $supplierName);
                            }
                        }
                    }
                }

                /** Get the current month start date */
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();

                if(!empty($startDate) && !empty($endDate)){
                    $data=UploadedFiles::where('supplier_id',$id)->whereBetween('created_at', [$startDate, $endDate])->latest('created_at')
                    ->first();

                    if(!isset($data)){
                        $this->info('no issue  ' . $id);

                        $supplierName = CategorySupplier::where('id',$id)->value('supplier_name');
                        $email = 'vishustaple.in@gmail.com';
                        
                        $this->sendEmail($email, $supplierName);
                    }
                }
            }
        }
    }

    /** Another function */
    protected function sendEmail($email, $supplierName){
        try {
            Mail::send('mail.pendingfile', ['suppliername' => $supplierName], function ($m) use ($email) {
                $m->from($email, 'Supplier Admin');
                $m->to('supplieradmin@gmail.com')->subject('Pending Files else');
            });

            $this->info('Email sent successfully');

        } catch (\Exception $e) {
            /** Handle the exception here */
            $this->error('Email sending failed: ' . $e->getMessage());
        }
    }
}
