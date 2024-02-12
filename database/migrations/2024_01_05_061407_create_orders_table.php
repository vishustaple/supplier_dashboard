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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('customer_number', 255);
            $table->decimal('amount', 10, 2);
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('data_id')->nullable();
            $table->datetime('date');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('data_id')->references('id')->on('uploaded_files');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('supplier_id')->references('id')->on('suppliers');
            // $table->foreign('record_type_id')->references('id')->on('record_types');
            /** Add other foreign key constraints if needed */
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
