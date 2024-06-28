<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShowPermissions extends Model
{
    use HasFactory;

    protected $table = 'show_permissions';

    protected $fillable = ['permission_name'];

    public function suppliers(){
        return $this->belongsToMany(CategorySupplier::class, 'supplier_permissions');
    }
}