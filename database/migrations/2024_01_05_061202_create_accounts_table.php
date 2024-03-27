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
        // Schema::create('accounts', function (Blueprint $table) {
            // $table->id();
            // $table->string('customer_number')->nullable();
            // $table->string('alies')->nullable();
            // $table->string('account_name')->nullable();
            // $table->string('volume_rebate')->nullable();
            // $table->string('sales_representative')->nullable();
            // $table->string('customer_service_representative')->nullable();
            // $table->string('member_rebate')->nullable();
            // $table->string('temp_active_date')->nullable();
            // $table->string('temp_end_date')->nullable();
            // $table->string('internal_reporting_name')->nullable();
            // $table->string('qbr')->nullable();
            // $table->string('spend_name')->nullable();
            // $table->string('supplier_acct_rep')->nullable();
            // $table->string('management_fee')->nullable();
            // $table->string('record_type')->nullable();
            // $table->unsignedBigInteger('category_supplier');
            // $table->string('cpg_sales_representative')->nullable();
            // $table->string('cpg_customer_service_rep')->nullable();
            // $table->string('sf_cat')->nullable();
            // $table->string('rebate_freq')->nullable();
            // $table->string('comm_rate')->nullable();
            // $table->unsignedBigInteger('parent_id')->nullable();
            // $table->unsignedBigInteger('created_by');
            // $table->timestamps();

            // $table->foreign('created_by')->references('id')->on('users');
            // $table->foreign('parent_id')->references('id')->on('accounts');
            // $table->foreign('category_supplier')->references('id')->on('suppliers');
            // /** Add other foreign key constraints if needed */
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Schema::dropIfExists('accounts');
    }
};
