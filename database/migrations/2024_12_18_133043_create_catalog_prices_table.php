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
        Schema::create('catalog_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_item_id')->constrained()->onDelete('cascade'); // Assuming a foreign key to catalog_items table
            $table->foreignId('catalog_price_type_id')->constrained()->onDelete('cascade'); // Assuming a foreign key to catalog_price_types table
            $table->foreignId('customer_id')->constrained()->onDelete('cascade'); // Assuming a foreign key to customers table
            $table->boolean('core_list')->default(false);
            $table->decimal('value', 10, 2); // Assuming the value is a decimal
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalog_prices');
    }
};
