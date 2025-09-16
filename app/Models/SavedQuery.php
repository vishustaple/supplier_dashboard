<?php

namespace App\Models;

use Illuminate\Database\Eloquent\{Model, Factories\HasFactory};

class SavedQuery extends Model
{
    use HasFactory;

    protected $fillable = ['query', 'title', 'created_by'];

    public function users() {
        return $this->belongsTo(User::class, 'created_by');
    }
}
