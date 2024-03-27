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
        Schema::create('office_depot_order', function (Blueprint $table) {
            $table->id();
            $table->string('customer_grandparent_id')->nullable();
            $table->string('customer_grandparent_nm')->nullable();
            $table->string('customer_parent_id')->nullable();
            $table->string('customer_parent_nm')->nullable();
            $table->string('customer_id')->nullable();
            $table->string('customer_nm')->nullable();
            $table->string('dept')->nullable();
            $table->string('class')->nullable();
            $table->string('subclass')->nullable();
            $table->string('sku')->nullable();
            $table->string('manufacture_item')->nullable();
            $table->string('manufacture_name')->nullable();
            $table->text('product_description')->nullable();
            $table->string('core_flag')->nullable();
            $table->string('maxi_catalog_whole_sale_flag')->nullable();
            $table->string('uom')->nullable();
            $table->string('private_brand')->nullable();
            $table->string('green_shade')->nullable();
            $table->string('qty_shipped')->nullable();
            $table->string('unit_net_price')->nullable();
            $table->string('unit_web_price')->nullable();
            $table->string('total_spend')->nullable();
            $table->string('shipto_location')->nullable();
            $table->string('contact_name')->nullable();
            $table->date('shipped_date')->nullable();
            $table->string('invoice')->nullable();
            $table->string('payment_method')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('office_depot_order');
    }
};
