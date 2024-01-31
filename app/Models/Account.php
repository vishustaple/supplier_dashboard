<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $table = 'accounts';

     /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'qbr',
        'sf_cat',
        'comm_rate',
        'parent_id',
        'spend_name',
        'created_by',
        'record_type',
        'rebate_freq',
        'customer_name',
        'member_rebate',
        'management_fee',
        'record_type_id',
        'customer_number',
        'supplier_acct_rep',
        'category_supplier',
        'internal_reporting_name',
        'cpg_sales_representative',
        'cpg_customer_service_rep',
    ];
    
    public function parent(){
        return $this->belongsTo(Account::class, 'parent_id');
    }
}
