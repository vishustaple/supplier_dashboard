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
        Schema::table('show_power_bi', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable()->after('column_name'); // Adjust column_name
            $table->unsignedBigInteger('deleted_by')->nullable()->after('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('show_power_bi', function (Blueprint $table) {
            $table->dropColumn(['created_by', 'deleted_by']);
        });
    }
};
