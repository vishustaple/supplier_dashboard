<?php

namespace App\Models;

use Illuminate\Database\Eloquent\{Model, Factories\HasFactory};

class ProductDetailsCategory extends Model
{
    use HasFactory;

    protected $table = 'product_details_category';

    protected $fillable = [
        'category_name',
    ];
}
