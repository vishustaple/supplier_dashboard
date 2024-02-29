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
        Schema::create('lyreco_order', function (Blueprint $table) {
            $table->id();
            $table->string('payer')->nullable();
            $table->string('name_payer')->nullable();
            $table->string('sold_to_pt')->nullable();
            $table->string('name_sold_to_party')->nullable();
            $table->string('ship_to')->nullable();
            $table->string('name_ship_to')->nullable();
            $table->string('name_3_plus_name_4_ship_to')->nullable();
            $table->string('street_ship_to')->nullable();
            $table->string('district_ship_to')->nullable();
            $table->string('postalcode_ship_to')->nullable();
            $table->string('city_ship_to')->nullable();
            $table->string('country_ship_to')->nullable();
            $table->string('leader_customer_1')->nullable();
            $table->string('leader_customer_2')->nullable();
            $table->string('leader_customer_3')->nullable();
            $table->string('leader_customer_4')->nullable();
            $table->string('leader_customer_5')->nullable();
            $table->string('leader_customer_6')->nullable();
            $table->string('product_hierarchy')->nullable();
            $table->string('section')->nullable();
            $table->string('family')->nullable();
            $table->string('category')->nullable();
            $table->string('sub_category')->nullable();
            $table->string('material')->nullable();
            $table->string('material_description')->nullable();
            $table->string('ownbrand')->nullable();
            $table->string('green_product')->nullable();
            $table->string('nbs')->nullable();
            $table->string('customer_material')->nullable();
            $table->string('customer_description')->nullable();
            $table->string('sales_unit')->nullable();
            $table->string('qty_in_sku')->nullable();
            $table->string('sales_deal')->nullable();
            $table->string('purchase_order_type')->nullable();
            $table->string('qty_in_sales_unit_p')->nullable();
            $table->string('quantity_in_sku_p')->nullable();
            $table->string('number_of_orders_p')->nullable();
            $table->string('sales_amount_p')->nullable();
            $table->string('tax_amount_p')->nullable();
            $table->string('net_sales_p')->nullable();
            $table->string('avg_selling_price_p')->nullable();
            $table->string('document_date')->nullable();
            $table->string('sales_document')->nullable();
            $table->string('po_number')->nullable();
            $table->string('bpo_number')->nullable();
            $table->string('invoice_list')->nullable();
            $table->string('billing_document')->nullable();
            $table->date('billing_date')->nullable();
            $table->string('cac_number')->nullable();
            $table->string('cac_description')->nullable();
            $table->string('billing_month_p')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lyreco_order');
    }
};
