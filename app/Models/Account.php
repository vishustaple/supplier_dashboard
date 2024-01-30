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
        'parent_id',
        'created_by',
        'customer_name',
        'record_type_id',
        'customer_number',
    ];
    
    public function parent(){
        return $this->belongsTo(Account::class, 'parent_id');
    }
}
