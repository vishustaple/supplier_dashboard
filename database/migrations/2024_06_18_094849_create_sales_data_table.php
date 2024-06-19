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
        Schema::create('staples_orders_data', function (Blueprint $table) {
            $table->id();
            $table->string("data_id")->nullable();
            $table->string("master_customer_number")->nullable();
            $table->string("master_customer_name")->nullable();
            $table->string("bill_to_number")->nullable();
            $table->string("bill_to_name")->nullable();
            $table->string("ship_to_number")->nullable();
            $table->string("ship_to_name")->nullable();
            $table->string("ship_to_line1_address")->nullable();
            $table->string("ship_to_line2_address")->nullable();
            $table->string("ship_to_line3_address")->nullable();
            $table->string("ship_to_city")->nullable();
            $table->string("ship_to_state")->nullable();
            $table->string("ship_to_zip")->nullable();
            $table->string("vendor_part_number")->nullable();
            $table->string("item_description")->nullable();
            $table->string("primary_product_hierarchy")->nullable();
            $table->string("diversity")->nullable();
            $table->string("diversity_code")->nullable();
            $table->string("diversity_sub_type_cd")->nullable();
            $table->string("selling_unit_measure_qty")->nullable();
            $table->string("vendor_name")->nullable();
            $table->string("recycled_flag")->nullable();
            $table->string("recycled")->nullable();
            $table->string("product_post_consumer_content")->nullable();
            $table->string("remanufactured_refurbished_flag")->nullable();
            $table->string("eco_feature")->nullable();
            $table->string("eco_sub_feature")->nullable();
            $table->string("eco_id")->nullable();
            $table->string("budget_center_name")->nullable();
            $table->string("invoice_date")->nullable();
            $table->string("invoice_number")->nullable();
            $table->string("on_contract")->nullable();
            $table->string("order_contact")->nullable();
            $table->string("order_contact_phone_number")->nullable();
            $table->string("order_date")->nullable();
            $table->string("order_method_description")->nullable();
            $table->string("order_number")->nullable();
            $table->string("payment_method_code")->nullable();
            $table->string("payment_method_code1")->nullable();
            $table->string("sell_uom")->nullable();
            $table->string("ship_to_contact")->nullable();
            $table->date("shipped_date")->nullable();
            $table->string("sku")->nullable();
            $table->string("transaction_source_system")->nullable();
            $table->string("transaction_source_system1")->nullable();
            $table->string("group_id")->nullable();
            $table->string("group_id1")->nullable();
            $table->string("qty")->nullable();
            $table->decimal("adj_gross_sales")->nullable();
            $table->decimal("avg_sell_price")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staples_orders_data');
    }
};
