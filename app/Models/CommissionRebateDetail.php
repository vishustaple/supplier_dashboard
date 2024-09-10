<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
        'commission_rebate_id',	
        'commission_percentage',	
        'volume_rebate_percentage',	
    ];
}
