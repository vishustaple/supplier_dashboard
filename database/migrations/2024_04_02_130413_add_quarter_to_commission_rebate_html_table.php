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
        Schema::create('commission_rebate_html', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('commission_rebate_id');
            $table->unsignedBigInteger('sales_rep');
            $table->string('month')->nullable();
            $table->string('commissions')->nullable();
            $table->string('volume_rebate')->nullable();
            $table->string('spend')->nullable();
            $table->tinyInteger('approved')->default(0)->comment("1 => Yes, 0 => No,");
            $table->tinyInteger('paid')->default(0)->comment("1 => Yes, 0 => No,");
            $table->text('content');
            $table->timestamps();
            $table->foreign('sales_rep')->references('id')->on('sales_team');
            $table->foreign('commission_rebate_id')->references('id')->on('commissions_rebate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commission_rebate_html');
    }
};
