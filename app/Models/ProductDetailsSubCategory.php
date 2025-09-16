<?php

namespace App\Models;

use Illuminate\Database\Eloquent\{Model, Factories\HasFactory};

class ProductDetailsSubCategory extends Model
{
    use HasFactory;

    protected $connection = 'second_db';
    protected $table = 'product_details_sub_category';

    protected $fillable = [
        'category_id',
        'sub_category_name',
    ];
}
