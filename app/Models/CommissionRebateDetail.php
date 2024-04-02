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
        'month',
        'spend',
        'quarter',
        'end_date',
        'approved',
        'supplier',
        'sales_rep',
        'start_date',
        'commission',
        'account_name',
        'volume_rebate',
        'commission_rebate_id',
        'commission_percentage',
        'volume_rebate_percentage',
    ];
}
