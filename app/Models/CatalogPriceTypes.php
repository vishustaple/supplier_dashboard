<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CatalogPriceTypes extends Model
{
    use HasFactory;

    protected $connection = 'second_db';
    protected $table = 'catalog_price_types';

    protected $fillable = [
        'name',
        'supplier_id',
    ];
}
