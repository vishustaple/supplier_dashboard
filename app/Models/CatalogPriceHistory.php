<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CatalogPriceHistory extends Model
{
    use HasFactory;

    protected $table = 'catalog_price_history';

    protected $fillable = [
        'May',
        'year',
        'June',
        'July',
        'March',
        'April',
        'August',
        'January',
        'October',
        'February',
        'November',
        'December',
        'core_list',
        'September',
        'customer_id',
        'catalog_item_id',
        'catalog_price_type_id',
    ];
}
