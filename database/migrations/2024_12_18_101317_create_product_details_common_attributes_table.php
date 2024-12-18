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
        Schema::create('product_details_common_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sub_category_id')->constrained('product_details_sub_category')->onDelete('cascade');  // Foreign key reference to the product_details_sub_category table
            $table->string('attribute_name');  // Column for the attribute name
            $table->string('type');  // Column for the type of the attribute (e.g., text, number, etc.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_details_common_attributes');
    }
};
