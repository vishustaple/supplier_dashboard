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
        Schema::create('catalog_items1', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');  // Foreign key reference to the suppliers table
            $table->foreignId('industry_id')->constrained('industries')->onDelete('cascade');  // Foreign key reference to the industries table
            $table->foreignId('category_id')->constrained('product_details_category')->onDelete('cascade');  // Foreign key reference to the product_details_category table
            $table->foreignId('sub_category_id')->constrained('product_details_sub_category')->onDelete('cascade');  // Foreign key reference to the product_details_sub_category table
            $table->string('sku');  // Column for SKU
            $table->string('vendor_part_number');  // Column for vendor part number
            $table->string('name');  // Column for the catalog item name
            $table->text('short_description')->nullable();  // Column for the short description
            $table->text('long_description')->nullable();  // Column for the long description
            $table->integer('quantity')->default(0);  // Column for the quantity of the item
            $table->integer('count')->default(1);  // Column for the count (quantity in stock or av
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalog_items1');
    }
};
