<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'customer_number',
        'product_sku',
        'product_details_id',
        'amount',
        'invoice_no',
        'invoice_date',
        'created_by',
    ];
}
