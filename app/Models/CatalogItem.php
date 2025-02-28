<?php

namespace App\Models;

use Illuminate\Database\Eloquent\{Model, Factories\HasFactory};

class CatalogItem extends Model
{
    use HasFactory;

    protected $table = 'catalog_items';
}
