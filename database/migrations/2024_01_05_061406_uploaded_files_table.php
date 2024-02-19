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
        Schema::create('uploaded_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_id');
            $table->string('file_name', 255);
            $table->tinyInteger('cron')->default(0); // Assuming TINYINT(2) is used for cron
            $table->datetime('start_date');
            $table->datetime('end_date');
            $table->unsignedBigInteger('created_by');
            $table->timestamps(); // This will automatically create 'created_at' and 'updated_at' columns
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('supplier_id')->references('id')->on('suppliers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uploaded_files');
    }
};
