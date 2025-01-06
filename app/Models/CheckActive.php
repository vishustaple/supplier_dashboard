<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckActive extends Model
{
    use HasFactory;

    protected $table = 'check_active';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'active',
        'catalog_item_id',
        'catalog_price_type_id'
    ];
}
