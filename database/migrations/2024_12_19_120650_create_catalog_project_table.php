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
        /** Industries Table */
        Schema::create('industries', function (Blueprint $table) {
            $table->id();
            $table->string('industry_name');
            $table->timestamps();
        });

        /** Manufacturers Table */
        Schema::create('manufacturers', function (Blueprint $table) {
            $table->id();
            $table->string('manufacturer_name');
            $table->timestamps();
        });

        /** Product Details Category Table */
        Schema::create('product_details_category', function (Blueprint $table) {
            $table->id();
            $table->string('category_name');
            $table->timestamps();
        });

        /** Product Details Sub-Category Table */
        Schema::create('product_details_sub_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('product_details_category')->onDelete('cascade');
            $table->string('sub_category_name');
            $table->timestamps();
        });

        /** Product Details Common Attributes Table */
        Schema::create('product_details_common_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sub_category_id')->constrained('product_details_sub_category')->onDelete('cascade');
            $table->string('attribute_name');
            $table->string('type')->nullable();
            $table->timestamps();
        });

        /** Catalog Items Table */
        Schema::create('catalog_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
            $table->foreignId('industry_id')->constrained('industries')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('product_details_category')->onDelete('cascade');
            $table->foreignId('sub_category_id')->nullable()->constrained('product_details_sub_category')->onDelete('set null');
            $table->foreignId('manufacturer_id')->nullable()->constrained('manufacturers')->onDelete('set null');
            $table->string('sku');
            $table->string('unspsc');
            $table->string('manufacturer_number');
            $table->string('catalog_item_name');
            $table->string('supplier_shorthand_name');
            $table->integer('quantity_per_unit');
            $table->string('unit_of_measure');
            $table->string('catalog_item_url');
            $table->timestamps();
        });

        /** Product Details Common Values Table */
        Schema::create('product_details_common_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_item_id')->constrained('catalog_items')->onDelete('cascade');
            $table->foreignId('common_attribute_id')->constrained('product_details_common_attributes')->onDelete('cascade');
            $table->string('value');
            $table->timestamps();
        });

        /** Product Details Raw Values Table */
        Schema::create('product_details_raw_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_item_id')->constrained('catalog_items')->onDelete('cascade');
            $table->json('raw_values');
            $table->timestamps();
        });

        /** Catalog Price Types Table */
        Schema::create('catalog_price_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
            $table->timestamps();
        });

        /** Catalog Prices Table */
        Schema::create('catalog_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_item_id')->constrained('catalog_items')->onDelete('cascade');
            $table->foreignId('catalog_price_type_id')->constrained('catalog_price_types')->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null');
            $table->boolean('core_list')->default(false);
            $table->decimal('value', 10, 2);
            $table->date('date');
            $table->timestamps();
        });

        /** Catalog Price History Table */
        Schema::create('catalog_price_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_item_id')->constrained('catalog_items')->onDelete('cascade');
            $table->foreignId('catalog_price_type_id')->constrained('catalog_price_types')->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null');
            // $table->decimal('value', 10, 2);
            /** Add columns for each month */
            $months = [
                'January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'
            ];

            foreach ($months as $month) {
                $table->decimal(strtolower($month), 10, 2)->nullable();
            }
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalog_price_history');
        Schema::dropIfExists('catalog_prices');
        Schema::dropIfExists('catalog_price_types');
        Schema::dropIfExists('product_details_raw_values');
        Schema::dropIfExists('product_details_common_values');
        Schema::dropIfExists('catalog_items');
        Schema::dropIfExists('product_details_common_attributes');
        Schema::dropIfExists('product_details_sub_category');
        Schema::dropIfExists('product_details_category');
        Schema::dropIfExists('manufacturers');
        Schema::dropIfExists('industries');
    }
};
