<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UploadedFiles extends Model
{
    use HasFactory;
     /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'supplier_id',
        'file_name',
        'cron',
    ];
    public function getsupplier()
    {
        return $this->hasOne(CategorySupplier::class, 'supplier_id', 'id');
    }
}
