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
        Schema::create('check_active', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_price_type_id')->constrained()->onDelete('cascade');
            $table->boolean('active')->default(0);
            $table->foreignId('catalog_item_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('check_active');
    }
};
