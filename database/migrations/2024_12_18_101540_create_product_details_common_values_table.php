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
        Schema::create('product_details_common_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_item_id')->constrained('catalog_items')->onDelete('cascade');  // Foreign key reference to the catalog_items table
            $table->foreignId('common_attribute_id')->constrained('product_details_common_attributes')->onDelete('cascade');  // Foreign key reference to the product_details_common_attributes table
            $table->string('value');  // Column for the value of the common attribu
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_details_common_values');
    }
};
