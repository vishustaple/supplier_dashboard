<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductDetailsCommonAttribute extends Model
{
    use HasFactory;

    protected $table = 'product_details_common_attributes';

    protected $fillable = [
        'type',
        'attribute_name',
        'sub_category_id',
    ];
}
