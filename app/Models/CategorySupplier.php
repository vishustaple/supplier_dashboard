<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategorySupplier extends Model
{
    use HasFactory;

    protected $table = 'category_suppliers';

    protected $fillable = [
        'supplier_name',
        'created_by',
    ];
}
