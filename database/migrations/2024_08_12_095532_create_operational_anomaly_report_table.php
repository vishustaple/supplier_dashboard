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
        Schema::create('operational_anomaly_report', function (Blueprint $table) {
            $table->id();
            $table->string('account_name');
            $table->string('supplier_name');
            $table->decimal('fifty_two_wk_avg');
            $table->decimal('ten_week_avg');
            $table->decimal('two_wk_avg_percentage');
            $table->decimal('drop');
            $table->decimal('median');
            $table->string('year');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operational_anomaly_report');
    }
};
