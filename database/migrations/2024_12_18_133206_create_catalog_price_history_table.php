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
        Schema::create('catalog_price_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_item_id')->constrained()->onDelete('cascade'); // Foreign key to catalog_items table
            $table->foreignId('catalog_price_type_id')->constrained()->onDelete('cascade'); // Foreign key to catalog_price_types table
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('cascade'); // Foreign key to customers table, nullable
            $table->boolean('core_list')->default(false); // Default to false
            $table->decimal('value', 10, 2); // Price value as decimal
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalog_price_history');
    }
};
