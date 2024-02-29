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
        Schema::create('wb_mason_order', function (Blueprint $table) {
            $table->id();
            $table->string('sales_id')->nullable();
            $table->string('customer_num')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('invoice_num')->nullable();
            $table->date('invoice_date')->nullable();
            $table->string('po_number')->nullable();
            $table->string('cost_center_code')->nullable();
            $table->string('cost_center_value')->nullable();
            $table->string('dlv_name')->nullable();
            $table->string('dlv_street')->nullable();
            $table->string('dlv_city')->nullable();
            $table->string('dlv_state')->nullable();
            $table->string('dlv_zip')->nullable();
            $table->string('item_num')->nullable();
            $table->string('item_name')->nullable();
            $table->string('category')->nullable();
            $table->string('category_umbrella')->nullable();
            $table->string('price_method')->nullable();
            $table->string('uom')->nullable();
            $table->string('current_list')->nullable();
            $table->string('qty')->nullable();
            $table->string('ext_price')->nullable();
            $table->string('line_tax')->nullable();
            $table->string('line_total')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wb_mason_order');
    }
};
