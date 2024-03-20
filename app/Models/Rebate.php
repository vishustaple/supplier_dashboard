<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rebate extends Model
{
    use HasFactory;
    
    protected $table = 'rebate';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'account_name',
        'volume_rebate',
        'incentive_rebate'
    ];
}
