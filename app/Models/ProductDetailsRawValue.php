<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductDetailsRawValue extends Model
{
    use HasFactory;

    protected $table = 'product_details_raw_values';

    protected $fillable = [
        'raw_values',
        'catalog_item_id',
    ];
}
