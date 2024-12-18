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
        Schema::create('catalog_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');  // Foreign key reference to the suppliers table
            $table->foreignId('industry_id')->constrained('industries')->onDelete('cascade');  // Foreign key reference to the industries table
            $table->foreignId('category_id')->constrained('product_details_category')->onDelete('cascade');  // Foreign key reference to the product_details_category table
            $table->foreignId('sub_category_id')->constrained('product_details_sub_category')->onDelete('cascade');  // Foreign key reference to the product_details_sub_category table
            $table->foreignId('manufacturer_id')->nullable()->constrained('manufacturers')->onDelete('set null');  // Foreign key reference to the manufacturers table (nullable)
            $table->string('sku');  // Column for SKU
            $table->string('unspsc');  // Column for UNSPSC code
            $table->string('manufacturer_number');  // Column for the manufacturer's number
            $table->string('catalog_item_name');  // Column for the catalog item name
            $table->string('supplier_shorthand_name');  // Column for supplier shorthand name
            $table->decimal('quantity_per_unit', 10, 2);  // Column for quantity per unit
            $table->string('unit_of_measure');  // Column for unit of measure
            $table->string('catalog_item_url');  // Column for catalog item URL
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalog_items');
    }
};
