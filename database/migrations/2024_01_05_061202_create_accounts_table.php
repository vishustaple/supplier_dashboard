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
        Schema::create('accounts', function (Blueprint $table) {
                $table->id();
                $table->string('customer_number', 20);
                $table->string('customer_name', 255);
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->string('internal_reporting_name', 255)->nullable();
                $table->string('qbr', 255)->nullable();
                $table->string('spend_name', 255)->nullable();
                $table->string('supplier_acct_rep', 255)->nullable();
                $table->string('management_fee', 20)->nullable();
                $table->string('record_type', 255)->nullable();
                $table->string('category_supplier', 255)->nullable();
                $table->string('cpg_sales_representative', 255)->nullable();
                $table->string('cpg_customer_service_rep', 255)->nullable();
                $table->string('sf_cat', 255)->nullable();
                $table->string('rebate_freq', 255)->nullable();
                $table->string('member_rebate', 255)->nullable();
                $table->string('comm_rate', 255)->nullable();
                $table->unsignedBigInteger('created_by');
                $table->timestamps();            
                $table->foreign('parent_id')->references('id')->on('accounts');
                $table->foreign('created_by')->references('id')->on('users');
                /** Add other foreign key constraints if needed */
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
