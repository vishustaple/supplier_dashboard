<?php

namespace App\Models;

use Illuminate\Database\Eloquent\{Model, Factories\HasFactory};

class Industry extends Model
{
    use HasFactory;

    protected $connection = 'second_db';
    protected $table = 'industries';
}
