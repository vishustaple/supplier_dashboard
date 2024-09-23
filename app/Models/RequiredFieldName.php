<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequiredFieldName extends Model
{
    use HasFactory;

    protected $table = 'required_fields';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'field_name',
        'fields_select_name'
    ];
}
