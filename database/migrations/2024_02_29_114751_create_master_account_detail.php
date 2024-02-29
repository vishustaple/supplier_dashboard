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
            $table->string('account_number');
            $table->string('customer_name');
            $table->string('account_name');
            $table->string('record_type');
            $table->string('volume_rebate');
            $table->string('category_supplier');
            $table->string('cpg_sales_representative');
            $table->string('cpg_customer_service_rep');
            $table->string('parent_id');
            $table->string('parent_name');
            $table->string('grandparent_id');
            $table->string('grandparent_name');
            $table->string('member_rebate');
            $table->string('temp_active_date');
            $table->string('temp_end_date');
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
