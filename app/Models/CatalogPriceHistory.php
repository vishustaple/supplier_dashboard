<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CatalogPriceHistory extends Model
{
    use HasFactory;

    protected $connection = 'second_db';
    protected $table = 'catalog_price_history';

    protected $fillable = [
        'catalog_item_id',
        'catalog_price_type_id',
        'customer_id',
        'year',
        'january',
        'february',
        'march',
        'april',
        'may',
        'june',
        'july',
        'august',
        'september',
        'october',
        'november',
        'december',
    ];
}
