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
        Schema::create('catalog_attachments', function (Blueprint $table) {
            $table->id(); /** Equivalent to `bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT` */
            $table->unsignedBigInteger('supplier_id');
            $table->string('file_name', 255);
            $table->tinyInteger('cron')->default(0);
            $table->tinyInteger('delete')->default(0);
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps(); /** Includes `created_at` and `updated_at` with nullable default */
            $table->softDeletes(); /** Includes `deleted_at` */
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalog_attachments');
    }
};
