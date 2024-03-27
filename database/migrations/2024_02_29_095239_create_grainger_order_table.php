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
        Schema::create('grainger_order', function (Blueprint $table) {
            $table->id();
            $table->string('track_code')->nullable();
            $table->string('track_code_name')->nullable();
            $table->string('sub_track_code')->nullable();
            $table->string('sub_track_code_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('account_name')->nullable();
            $table->string('material')->nullable();
            $table->text('material_description')->nullable();
            $table->string('material_segment')->nullable();
            $table->string('brand_name')->nullable();
            $table->date('bill_date')->nullable();
            $table->string('billing_document')->nullable();
            $table->string('purchase_order_number')->nullable();
            $table->string('sales_document')->nullable();
            $table->string('name_of_orderer')->nullable();
            $table->string('sales_office')->nullable();
            $table->string('sales_office_name')->nullable();
            $table->string('bill_line_no')->nullable();
            $table->string('active_price_point')->nullable();
            $table->string('billing_qty')->nullable();
            $table->string('purchase_amount')->nullable();
            $table->string('freight_billed')->nullable();
            $table->string('tax_billed')->nullable();
            $table->string('total_invoice_price')->nullable();
            $table->string('actual_price_paid')->nullable();
            $table->string('reference_price')->nullable();
            $table->string('ext_reference_price')->nullable();
            $table->string('diff')->nullable();
            $table->string('discount_percentage')->nullable();
            $table->string('invoice_number')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grainger_order');
    }
};
