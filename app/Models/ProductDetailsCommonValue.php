<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductDetailsCommonValue extends Model
{
    use HasFactory;

    protected $table = 'product_details_common_values';

    protected $fillable = [
        'value',
        'catalog_item_id',
        'common_attribute_id',
    ];
}
