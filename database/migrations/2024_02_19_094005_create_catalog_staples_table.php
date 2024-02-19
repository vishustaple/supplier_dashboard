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
        Schema::create('catalog_staples', function (Blueprint $table) {
            $table->id();
            $table->string('sku_number', 225)->nullable();
            $table->string('item_description', 225)->nullable();
            $table->string('staples_advantages_item_description', 225)->nullable();
            $table->string('selluom', 225)->nullable();
            $table->string('qty_in_selluom', 225)->nullable();
            $table->string('avg_sell_price', 225)->nullable();
            $table->string('primary_prod_cat', 225)->nullable();
            $table->string('secondary_prod_cat', 225)->nullable();
            $table->string('prod_class', 225)->nullable();
            $table->string('staple_own_brand', 225)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalog_staples');
    }
};
