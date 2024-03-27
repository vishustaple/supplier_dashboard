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
        Schema::create('commission_rebate_detail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('commission_rebate_id');
            $table->string('commission')->nullable();
            $table->string('volume_rebate')->nullable();
            $table->string('commission_percentage')->nullable();
            $table->string('volume_rebate_percentage')->nullable();
            $table->string('spend')->nullable();
            $table->string('start_date')->nullable();
            $table->string('end_date')->nullable();
            $table->tinyInteger('approved')->default(0)->comment("1 => Yes, 0 => No,");
            $table->tinyInteger('paid')->default(0)->comment("1 => Yes, 0 => No,");
            $table->unsignedBigInteger('supplier');
            $table->timestamps();
            $table->foreign('supplier')->references('id')->on('suppliers');
            $table->foreign('commission_rebate_id')->references('id')->on('commission_rebate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commission_rebate_detail');
    }
};
