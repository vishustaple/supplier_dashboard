<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductDetailsSubCategory extends Model
{
    use HasFactory;

    protected $table = 'product_details_sub_category';

    protected $fillable = [
        'category_id',
        'sub_category_name',
    ];
}
