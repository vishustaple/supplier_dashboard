<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategorySupplier extends Model
{
    use HasFactory;

    protected $table = 'suppliers';

    protected $fillable = [
        'created_by',
        'supplier_name',
    ];
}
