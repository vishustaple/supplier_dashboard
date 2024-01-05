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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('customer_number', 20);
            $table->string('customer_name', 255);
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('accounts');
            $table->foreign('created_by')->references('id')->on('users');
            // Add other foreign key constraints if needed 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
