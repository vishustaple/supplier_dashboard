<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class UploadedFiles extends Model
{
    use HasFactory, SoftDeletes;
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

    protected $dates = ['deleted_at'];

    public function runSoftDelete()
    {
        // Set the deleted_by value before soft deleting the record
        $this->setDeletedBy();

        // Perform the soft delete
        $this->{$this->getDeletedAtColumn()} = $time = $this->freshTimestamp();
        $this->update([$this->getDeletedAtColumn() => $this->fromDateTime($time)]);
    }

    protected function setDeletedBy()
    {
        if (auth()->check()) {
            $this->deleted_by = auth()->user()->id;
        }
    }
}
