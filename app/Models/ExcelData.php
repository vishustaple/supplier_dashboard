<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExcelData extends Model
{
    use HasFactory;
    
    protected $table = 'order_product_details';
    
     /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    
     protected $fillable = [
        'key',
        'value',
        'order_id',
        'file_name',
        'attachment_id',
    ];

    /** Define the relationship with the Supplier model */
    public function order() {
        return $this->belongsTo(order::class);
    }
}
