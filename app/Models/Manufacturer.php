<?php

namespace App\Models;

use Illuminate\Database\Eloquent\{Model, Factories\HasFactory};

class Manufacturer extends Model
{
    use HasFactory;

    protected $connection = 'second_db';
    protected $table = 'manufacturers';

    protected $fillable = [
        'manufacturer_name',
    ];
}
