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
        Schema::create('product_details_sub_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('product_details_category')->onDelete('cascade');  // Foreign key reference to the product_details_category table
            $table->string('sub_category_name');  // Column for the sub category name
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_details_sub_category');
    }
};
