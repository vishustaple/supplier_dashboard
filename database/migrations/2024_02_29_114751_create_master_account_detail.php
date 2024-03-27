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
        Schema::create('master_account_detail', function (Blueprint $table) {
            $table->id();
            $table->string('account_number')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('account_name')->nullable();
            $table->string('record_type')->nullable();
            $table->string('volume_rebate')->nullable();
            $table->string('category_supplier')->nullable();
            $table->string('cpg_sales_representative')->nullable();
            $table->string('cpg_customer_service_rep')->nullable();
            $table->string('parent_id')->nullable();
            $table->string('parent_name')->nullable();
            $table->string('grandparent_id')->nullable();
            $table->string('grandparent_name')->nullable();
            $table->string('member_rebate')->nullable();
            $table->string('temp_active_date')->nullable();
            $table->string('temp_end_date')->nullable();
            $table->timestamps();
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
