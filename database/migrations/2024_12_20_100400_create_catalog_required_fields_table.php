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
        Schema::create('catalog_required_fields', function (Blueprint $table) {
            $table->id(); /** Equivalent to `bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT` */
            $table->string('field_name', 255);
            $table->string('fields_select_name', 255);
            $table->timestamps(); /** Includes `created_at` and `updated_at` */
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalog_required_fields');
    }
};
