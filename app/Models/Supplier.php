<?php

namespace App\Models;

use Illuminate\Database\Eloquent\{Model, Factories\HasFactory};

class Supplier extends Model
{
  use HasFactory;

  protected $table = 'suppliers';

  protected $fillable = [
    'show',
    'supplier_name',
  ];

  public function uploadedFiles() {
    return $this->hasMany(UploadedFiles::class);
  }

  public function createdByUser() {
    return $this->belongsTo(User::class, 'created_by');
  }

  public static function getSearchSupplierData($search='') {
    if (!empty($search)) {
      $query = self::query()
      ->select('id', 'supplier_name')
      ->where('supplier_name', 'LIKE', '%' . $search . '%');
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
