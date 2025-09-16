<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CatalogRequiredFields extends Model
{
    use HasFactory;

    protected $connection = 'second_db';
    protected $table = 'catalog_required_fields';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'field_name',
        'fields_select_name'
    ];
}
