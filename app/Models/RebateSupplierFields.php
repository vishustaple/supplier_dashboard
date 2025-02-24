<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RebateSupplierFields extends Model
{
    use HasFactory;

    protected $table = 'rebate_supplier_fields';

    protected $fillable = [
        'type',
        'label',
        'deleted',
        'required',
        'raw_label',
        'supplier_id',
        'rebate_supplier_required_field_id',
    ];
}
