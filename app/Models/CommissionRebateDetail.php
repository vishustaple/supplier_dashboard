<?php

namespace App\Models;

use Illuminate\Database\Eloquent\{Model, Factories\HasFactory};

class CommissionRebateDetail extends Model
{
    use HasFactory;
    
    protected $table = 'commissions_rebate_detail';

    /**
    * The attributes that are mass assignable.
    *
    * @var array<int, string>
    */
    protected $fillable = [
        'month',
        'paid',	
        'spend',	
        'quarter',
        'supplier',	
        'end_date',	
        'approved',
        'sales_rep',	
        'paid_date',	
        'commissions',	
        'start_date',	
        'account_name',	
        'volume_rebate',	
        'commissions_rebate_id',	
        'commissions_percentage',	
        'volume_rebate_percentage',	
    ];
}
