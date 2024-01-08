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
        Schema::create('order_product_details', function (Blueprint $table) {
            $table->id();
            $table->string('product_name', 255);
            $table->string('product_brand', 255)->nullable();
            $table->unsignedBigInteger('record_type_id')->nullable();
            $table->unsignedBigInteger('category_supplier_id')->nullable();
            $table->text('product_description')->nullable();
            $table->timestamps();

            $table->foreign('record_type_id')->references('id')->on('record_types');
            $table->foreign('category_supplier_id')->references('id')->on('category_suppliers');
            // Add other foreign key constraints if needed
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_product_details');
    }
};
