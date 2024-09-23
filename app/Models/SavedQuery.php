<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavedQuery extends Model
{
    use HasFactory;

    protected $fillable = ['query', 'title', 'created_by'];

    public function users(){
        return $this->belongsTo(User::class, 'created_by');
    }
}
