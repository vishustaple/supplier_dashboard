<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\{Model, Factories\HasFactory};

class ManageColumns extends Model
{
    use HasFactory;
    
    protected $table = 'supplier_fields';

    protected $fillable = [
        'type',
        'label',
        'deleted',
        'required',
        'raw_label',
        'supplier_id',
        'required_field_id',
    ];

    public static function getRequiredColumns(){
        $columnValues = DB::table('supplier_fields')
        ->select('supplier_id', 'label')
        ->where('deleted', 0)
        ->whereNotNull('required_field_id')
        ->get();

        foreach ($columnValues as $value) {
            $jsArray[$value->supplier_id][] =  $value->label;
        }

        return $jsArray;
    }

    public static function getColumns(){
        $columnValues = DB::table('supplier_fields')
        ->select('supplier_id', 'label')
        ->where('deleted', 0)
        ->get();

        foreach ($columnValues as $value) {
            $jsArray[$value->supplier_id][] =  $value->label;
        }

        return $jsArray;
    }

    public static function cleanRows(array $array){
        foreach ($array as &$row) {
            $row = str_replace([' ', '_'], '', $row);
            $row = preg_replace('/[^\w\s]/', '', $row);
            $row = strtolower($row);
        }
        return $array;
    }
}
