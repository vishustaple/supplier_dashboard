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
        Schema::create('catalog_supplier_fields', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_id');
            $table->tinyInteger('required')->default(0);
            $table->unsignedBigInteger('catalog_required_field_id')->nullable();
            $table->string('raw_label', 255)->nullable();
            $table->string('label', 225);
            $table->string('type', 255)->nullable();
            $table->tinyInteger('deleted')->default(0);
            $table->timestamps();

            // Foreign Key Constraints
            $table->foreign('catalog_required_field_id')->references('id')->on('catalog_required_fields')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalog_supplier_fields');
    }
};
