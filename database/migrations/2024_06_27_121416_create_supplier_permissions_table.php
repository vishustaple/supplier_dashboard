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
        Schema::create('supplier_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_supplier_id');
            $table->foreignId('show_permissions_id');
            $table->timestamps();

            // Manually define foreign key constraints with unique names
            $table->foreign('category_supplier_id')->references('id')->on('suppliers');
            $table->foreign('show_permissions_id')->references('id')->on('show_permissions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_permissions');
    }
};
