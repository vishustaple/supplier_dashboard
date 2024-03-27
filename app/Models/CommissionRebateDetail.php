<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommissionRebateDetail extends Model
{
    use HasFactory;
    
    protected $table = 'commission_rebate_detail';

    
    /**
    * The attributes that are mass assignable.
    *
    * @var array<int, string>
    */
    protected $fillable = [
        'paid',
        'spend',
        'end_date',
        'approved',
        'commission_rebate_id',
        'start_date',
        'supplier',
        'commission',
        'volume_rebate',
        'commission_percentage',
        'volume_rebate_percentage',
    ];
}
