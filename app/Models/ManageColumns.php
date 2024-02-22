<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManageColumns extends Model
{
    use HasFactory;
    protected $table = 'manage_columns';
    protected $fillable = [
        'supplier_id',
        'required',
        'field_name',
    ];
    public static function getColumns(){
        $supplierIDsGrouped = ManageColumns::groupBy('supplier_id')->pluck('supplier_id')->toArray();

        $allSupplierData = [];
        foreach ($supplierIDsGrouped as $supplier) {
            $columns = ManageColumns::where('required', '1')
                ->where('supplier_id', $supplier)
                ->pluck('field_name')
                ->toArray();
            $allSupplierData[$supplier] = $columns;
        }
        
        $jsArray = '[';
        
        foreach ($allSupplierData as $supplierId => $subArray) {
            if (!empty($subArray)) {
                // Remove single quotes from each string element
                $cleanedSubArray = array_map(function ($item) {
                    return str_replace("'", "", $item);
                }, $subArray);
                $jsArray .= "'$supplierId' => ['" . implode("', '", $subArray) . "'],";
            } else {
                $jsArray .= "'$supplierId' => [],";
            }
        }
        
        $jsArray .= ']';
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
