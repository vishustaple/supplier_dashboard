<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CatalogItem extends Model
{
    use HasFactory;

    protected $table = 'catalog_items';

    protected $fillable = [
        'sku',
        'unspsc',
        'supplier_id',
        'industry_id',
        'category_id',
        'sub_category_id',
        'unit_of_measure',
        'manufacturer_id',
        'catalog_item_url',
        'catalog_item_name',
        'quantity_per_unit',
        'manufacturer_number',
        'catalog_price_type_id',
        'supplier_shorthand_name',
    ];
}
