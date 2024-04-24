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
        Schema::create('staples_order', function (Blueprint $table) {
            $table->id();
            $table->string('div_id');
            $table->string('master_customer_number_id');
            $table->string('master_customer_name_id');
            $table->string('bill_to_number_id');
            $table->string('bill_to_name_id');
            $table->string('ship_to_number_id');
            $table->string('ship_to_name_id');
            $table->string('ship_to_line1_address_id');
            $table->string('ship_to_line2_address_id');
            $table->string('ship_to_line3_address_id');
            $table->string('ship_to_city_id');
            $table->string('ship_to_state_id');
            $table->string('ship_to_zip_id');
            $table->string('primary_product_hierarchy_desc');
            $table->string('sku_id');
            $table->string('item_description_id');
            $table->string('vendor_name_id');
            $table->string('vendor_part_number_id');
            $table->string('sell_uom_id');
            $table->string('selling_unit_measure_qty_id');
            $table->string('order_date_id');
            $table->string('shipped_date_id');
            $table->string('order_number_id');
            $table->string('order_contact_id');
            $table->string('order_contact_phone_number_id');
            $table->string('order_method_code_id');
            $table->string('order_method_description_id');
            $table->string('transaction_source_system_desc');
            $table->string('sku_type_id');
            $table->string('on_contract_id');
            $table->string('invoice_number_id');
            $table->string('invoice_date_id');
            $table->string('qty');
            $table->string('adj_gross_sales');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staples_order');
    }
};
