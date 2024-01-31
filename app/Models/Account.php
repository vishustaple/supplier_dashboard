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
        'customer_number',
        'customer_name',
        'parent_id',
        'created_by',
    ];
    
    public function parent(){
        return $this->belongsTo(Account::class, 'parent_id');
    }
    public function grandparent()
    {
        return $this->parent()->with('parent');
    }

    public static function getHierarchy()
    {
        return self::with(['parent' => function ($query) {
            $query->with('parent');
        }])
            ->whereNotNull('parent_id')
            ->get();
    }
}
