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
        Schema::create('commission_rebate', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sales_rep');
            $table->string('commission')->nullable();
            $table->string('volume_rebate')->nullable();
            $table->string('spend')->nullable();
            $table->string('quarter')->nullable();
            $table->string('start_date')->nullable();
            $table->string('end_date')->nullable();
            $table->tinyInteger('approved')->default(0)->comment("1 => Yes, 0 => No,");
            $table->tinyInteger('paid')->default(0)->comment("1 => Yes, 0 => No,");
            $table->timestamps();
            $table->foreign('sales_rep')->references('id')->on('sales_team');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commission_rebate');
    }
};
