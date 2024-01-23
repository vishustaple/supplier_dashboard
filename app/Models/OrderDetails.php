<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderDetails extends Model
{
    use HasFactory;

    protected $table = 'order_details';
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_file_name',
        'invoice_number',
        'invoice_date',
        'created_by',
        'order_id',
    ];

    public static function randomInvoiceNum(){
        $min = 1000000000;  // Minimum 10-digit number
        $max = 9999999999;  // Maximum 10-digit number
        $number=mt_rand($min, $max);

        // Check if the generated number already exists in the orders table
        $existingNumber = self::where('invoice_number', $number)->exists();
        if(isset($existingNumber)){
            $number = mt_rand($min, $max);
        }

        return $number;
    }
}
