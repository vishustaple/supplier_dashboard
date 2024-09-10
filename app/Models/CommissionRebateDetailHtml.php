<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CommissionRebateDetailHtml extends Model
{
    use HasFactory;

    protected $table = 'commission_rebate_html';

    /**
    * The attributes that are mass assignable.
    *
    * @var array<int, string>
    */
    protected $fillable = [
        'paid',
        'month',
        'spend',
        'content',
        'approved',
        'sales_rep',
        'commissions',
        'volume_rebate',
        'commission_rebate_id',
    ];
}
