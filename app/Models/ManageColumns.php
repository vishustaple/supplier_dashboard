<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ManageColumns extends Model
{
    use HasFactory;
    
    protected $table = 'manage_columns';

    protected $fillable = [
        'supplier_id',
        'required',
        'field_name',
    ];

    public static function getRequiredColumns(){
        $columnValues = DB::table('manage_columns')->select('supplier_id', 'field_name')->where('required', 1)->get();

        foreach ($columnValues as $value) {
            $jsArray[$value->supplier_id][] =  $value->field_name;
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
