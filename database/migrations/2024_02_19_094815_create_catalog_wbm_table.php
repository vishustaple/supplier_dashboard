<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('catalog_wbm', function (Blueprint $table) {
            $table->id();
            $table->string('full_sku', 225)->nullable();
            $table->string('product_code', 225)->nullable();
            $table->string('manufacturer', 225)->nullable();
            $table->string('item_description', 225)->nullable();
            $table->string('uom', 225)->nullable();
            $table->string('unit_price', 225)->nullable();
            $table->string('list_price', 225)->nullable();
            $table->string('category', 225)->nullable();
            $table->string('category_umbrella', 225)->nullable();
            $table->string('wb_qpu', 225)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalog_wbm');
    }
};
