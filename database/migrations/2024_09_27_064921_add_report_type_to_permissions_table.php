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
        Schema::table('permissions', function (Blueprint $table) {
            /** Add 'report_type' after 'name' column */
            $table->integer('report_type')->after('name')->nullable()->comment('1 => report, 2 => powerbi report');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            /** Drop 'report_type' column if rolling back */
            $table->dropColumn('report_type');
        });
    }
};
