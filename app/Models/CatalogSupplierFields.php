<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CatalogSupplierFields extends Model
{
    use HasFactory;

    protected $table = 'catalog_supplier_fields';
    
    protected $fillable = [
        'type',
        'label',
        'deleted',
        'required',
        'raw_label',
        'supplier_id',
        'catalog_required_field_id',
    ];

    public static function getRequiredColumns(){
        $columnValues = DB::table('catalog_supplier_fields')
        ->select('supplier_id', 'label')
        ->where('deleted', 0)
        ->whereNotNull('catalog_required_field_id')
        ->get();

        foreach ($columnValues as $value) {
            $jsArray[$value->supplier_id][] =  $value->label;
        }

        return $jsArray;
    }

    public static function getColumns(){
        $columnValues = DB::table('catalog_supplier_fields')
        ->select('supplier_id', 'label')
        ->where('deleted', 0)
        ->get();

        foreach ($columnValues as $value) {
            $jsArray[$value->supplier_id][] =  $value->label;
        }

        return $jsArray;
    }
}
