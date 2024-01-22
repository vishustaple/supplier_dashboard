<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class UploadedFiles extends Model
{
    use HasFactory;
    const UPLOAD = 1;
    const CRON = 2;
    const PROCESSED = 3;
    
     /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'supplier_id',
        'file_name',
        'cron',
        'created_by',
        'start_date',
        'end_date',
    ];

}
