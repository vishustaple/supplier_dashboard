<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckCoreHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'core_list',
        'customer_id',
        'catalog_item_id',
        'price_file_date',
        'catalog_price_type_id',
    ];
}
