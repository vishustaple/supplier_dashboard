<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExcelData extends Model
{
    use HasFactory;
     /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'supplier_id',
        'file_name',
        'key',
        'value',
    ];

    /** Define the relationship with the Supplier model */
    // public function supplier()
    // {
    //     return $this->belongsTo(CategorySuppliers::class);
    // }
}
