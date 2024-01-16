<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $table = 'orders';
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'customer_number',
        'supplier_id',
        'amount',
        'invoice_no',
        'invoice_date',
        'created_by',
    ];
    

     public function random_invoice_num()
    {
        $min = 1000000000;  // Minimum 10-digit number
        $max = 9999999999;  // Maximum 10-digit number
        $number=mt_rand($min, $max);

        // Check if the generated number already exists in the orders table
        $existingNumber = $this->where('invoice_no', $number)->exists();
        if(isset($existingNumber)){
            $number = mt_rand($min, $max);
        }

        return $number;
        
    }

}
