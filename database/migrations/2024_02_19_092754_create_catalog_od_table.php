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
        Schema::create('catalog_od', function (Blueprint $table) {
            $table->id();
            $table->string('sku_number', 225)->nullable();
            $table->string('um', 225)->nullable();
            $table->string('vendor_prd', 225)->nullable();
            $table->string('item', 225)->nullable();
            $table->string('platinum_price', 225)->nullable();
            $table->string('platinum_price_method', 225)->nullable();
            $table->string('preferred_price', 225)->nullable();
            $table->string('preferred_price_method', 225)->nullable();
            $table->string('dept_description', 225)->nullable();
            $table->string('class_description', 225)->nullable();
            $table->string('sugg', 225)->nullable();
            $table->string('vendor_name', 225)->nullable();
            $table->string('mbe', 225)->nullable();
            $table->string('wbe', 225)->nullable();
            $table->string('recycled', 225)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalog_od');
    }
};
