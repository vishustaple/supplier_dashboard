<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
        'attachment_id',
        'order_id',
        'created_by',
        'invoice_date',
        'invoice_number',
        'order_file_name',
    ];


    public static function randomInvoiceNum($array = []){
        $min = 1000000000;  // Minimum 10-digit number
        $max = 9999999999;  // Maximum 10-digit number
        $number=mt_rand($min, $max);
       
      
        foreach ($array as $index => $innerArray) {
            // echo "Index: $index\n";
            
            foreach ($innerArray as $property => $value) {
                // echo "  $property: $value\n";
                // Check if the generated number already exists in the provided array
                if ($number == $value ) {
                    // If it exists, recursively call the function with the same array
                    $number = self::randomInvoiceNum($array);
                }
            }
            
            // echo "\n";
        }
       
        // Check if the generated number already exists in the orders table
        $existingNumber = self::where('invoice_number', $number)->exists();
        
        if(!empty($existingNumber)){
            $number = self::randomInvoiceNum($array);        
        }        
        return $number;
    }

  
}
