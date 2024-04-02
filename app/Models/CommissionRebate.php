<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommissionRebate extends Model
{
    use HasFactory;

    protected $table = 'commission_rebate';

    
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
        'start_date',
        'commission',
        'volume_rebate',
    ];
}
