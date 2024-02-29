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
            $table->string('master_customer')->nullable();
            $table->string('master_name')->nullable();
            $table->string('bill_to_number')->nullable();
            $table->string('bill_to_name')->nullable();
            $table->string('ship_to_number')->nullable();
            $table->string('ship_to_name')->nullable();
            $table->string('ship_to_address_line1')->nullable();
            $table->string('ship_to_address_line2')->nullable();
            $table->string('ship_to_address_line3')->nullable();
            $table->string('ship_to_city')->nullable();
            $table->string('ship_to_state')->nullable();
            $table->string('ship_to_zipcode')->nullable();
            $table->string('last_ship_date')->nullable();
            $table->string('ship_to_create_date')->nullable();
            $table->string('ship_to_status')->nullable();
            $table->string('line_item_budget_center')->nullable();
            $table->string('cust_po_rel')->nullable();
            $table->string('cust_po')->nullable();
            $table->string('order_contact')->nullable();
            $table->string('order_contact_phone')->nullable();
            $table->string('ship_to_contact')->nullable();
            $table->string('order_number')->nullable();
            $table->string('order_date')->nullable();
            $table->string('shipped_date')->nullable();
            $table->string('trans_ship_to_line3')->nullable();
            $table->string('shipment_number')->nullable();
            $table->string('trans_type_code')->nullable();
            $table->string('order_method_desc')->nullable();
            $table->string('pymt_type')->nullable();
            $table->string('pymt_method_desc')->nullable();
            $table->string('invoice_number')->nullable();
            $table->string('summary_invoice_number')->nullable();
            $table->date('invoice_date')->nullable();
            $table->string('cvnce_card_flag')->nullable();
            $table->string('sku_number')->nullable();
            $table->string('item_description')->nullable();
            $table->string('staples_advantage_item_description')->nullable();
            $table->string('sell_uom')->nullable();
            $table->string('qty_in_sell_uom')->nullable();
            $table->string('staples_own_brand')->nullable();
            $table->string('diversity_cd')->nullable();
            $table->string('diversity')->nullable();
            $table->string('diversity_subtype_cd')->nullable();
            $table->string('diversity_subtype')->nullable();
            $table->string('contract_flag')->nullable();
            $table->string('sku_type')->nullable();
            $table->string('trans_source_sys_cd')->nullable();
            $table->string('transaction_source_system')->nullable();
            $table->string('item_frequency')->nullable();
            $table->string('number_orders_shipped')->nullable();
            $table->string('qty')->nullable();
            $table->string('adj_gross_sales')->nullable();
            $table->string('avg_sell_price')->nullable();
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
