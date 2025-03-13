<?php

namespace App\Models;

use Illuminate\Database\Eloquent\{Model, Factories\HasFactory};

class OrderDetails extends Model
{
    use HasFactory;

    protected $table = 'order_details';
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'value',
        'order_id',
        'created_at',
        'attachment_id',
        'supplier_field_id',
    ];
}
