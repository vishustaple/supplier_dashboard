<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Supplier extends Model
{
  use HasFactory;

  protected $table = 'suppliers';

  protected $fillable = [
    'show',
    'supplier_name',
  ];

  public static function getSearchSupplierData($search=''){
    if (!empty($search)) {
      $query = self::query()->select('id', 'supplier_name')->where('supplier_name', 'LIKE', '%' . $search . '%');
      $results = $query->get();

      if ($results->isNotEmpty()) {
        foreach ($results as $value) {
          $finalArray[] = ['id' => $value->id, 'text' => $value->supplier_name];        
        }
      }

      return $finalArray;
    }
  }
}
