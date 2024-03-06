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
        Schema::create('commission', function (Blueprint $table) {
            $table->id();
            $table->string('customer');
            $table->unsignedBigInteger('supplier');
            $table->string('account_name');
            $table->string('commission');
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamps();
            $table->foreign('supplier')->references('id')->on('suppliers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commission');
    }
};
