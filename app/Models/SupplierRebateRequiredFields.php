<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierRebateRequiredFields extends Model
{
    use HasFactory;

    protected $table = 'rebate_supplier_required_fields';

    protected $fillable = [
        'field_name',
        'fields_select_name',
    ];
}
