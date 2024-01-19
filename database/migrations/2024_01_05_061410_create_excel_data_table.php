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
        Schema::create('order_product_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('file_name', 255);
            $table->string('key', 255);
            $table->text('value')->nullable();
            $table->timestamps();
            
            $table->foreign('order_id')->references('id')->on('orders'); /** Assuming 'orders' table exists */
            /** Add other foreign key constraints if needed */

            $table->index('file_name');
            $table->index('key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_product_details');
    }
};
