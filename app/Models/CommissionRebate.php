<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CommissionRebate extends Model
{
    use HasFactory;

    protected $table = 'commissions_rebate';

    
    /**
    * The attributes that are mass assignable.
    *
    * @var array<int, string>
    */
    protected $fillable = [
        'paid',
        'spend',
        'quarter',
        'end_date',
        'approved',
        'sales_rep',
        'paid_date',
        'start_date',
        'commissions',
        'volume_rebate',
    ];
}
