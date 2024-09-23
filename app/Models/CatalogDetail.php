<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CatalogDetail extends Model
{
    use HasFactory;

    protected $table = 'catalog_details';

     /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'file_name',
        'table_key',
        'created_by',
        'catalog_id',
        'table_value',
    ];
}
