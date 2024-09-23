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
        Schema::create('od_report', function (Blueprint $table) {
            $table->id();
            $table->string('supplier')->nullable();
            $table->string('account_name')->nullable();
            $table->tinyInteger('product_type')->default(0)->comment("1 => none core, 2 => core");
            $table->string('sku')->nullable();
            $table->string('description')->nullable();
            $table->string('uom')->nullable();
            $table->string('category')->nullable();
            $table->string('quantity_purchased')->nullable();
            $table->decimal('total_spend', 10, 2)->nullable();
            $table->decimal('unit_price_q1_price', 10, 2)->nullable();
            $table->decimal('unit_price_q2_price', 10, 2)->nullable();
            $table->decimal('unit_price_q3_price', 10, 2)->nullable();
            $table->decimal('unit_price_q4_price', 10, 2)->nullable();
            $table->decimal('web_price_q1_price', 10, 2)->nullable();
            $table->decimal('web_price_q2_price', 10, 2)->nullable();
            $table->decimal('web_price_q3_price', 10, 2)->nullable();
            $table->decimal('web_price_q4_price', 10, 2)->nullable();
            $table->decimal('lowest_price', 10, 2)->nullable();
            $table->string('date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('od_report');
    }
};
