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
        Schema::create('commissions_rebate_detail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('commissions_rebate_id');
            $table->unsignedBigInteger('sales_rep');
            $table->string('month')->nullable();
            $table->string('commissions')->nullable();
            $table->string('volume_rebate')->nullable();
            $table->string('commissions_percentage')->nullable();
            $table->string('volume_rebate_percentage')->nullable();
            $table->string('spend')->nullable();
            $table->string('start_date')->nullable();
            $table->string('end_date')->nullable();
            $table->tinyInteger('approved')->default(0)->comment("1 => Yes, 0 => No,");
            $table->tinyInteger('paid')->default(0)->comment("1 => Yes, 0 => No,");
            $table->unsignedBigInteger('supplier');
            $table->string('account_name')->nullable();
            $table->string('quarter')->nullable();
            
            $table->timestamps();
            $table->foreign('supplier')->references('id')->on('suppliers');
            $table->foreign('sales_rep')->references('id')->on('sales_team');
            $table->foreign('commissions_rebate_id')->references('id')->on('commissions_rebate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commissions_rebate_detail');
    }
};
