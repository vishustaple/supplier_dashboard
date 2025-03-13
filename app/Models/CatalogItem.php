<?php

namespace App\Models;

use Illuminate\Database\Eloquent\{Model, Factories\HasFactory};

class CatalogItem extends Model
{
    use HasFactory;

    protected $table = 'catalog_items';

    protected $fillable = [
        'sku',
        'unspsc',
        'active',
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
        'supplier_shorthand_name',
    ];
}
