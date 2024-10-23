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
            // Add the deleted_at column
            $table->timestamp('deleted_at')->nullable(); // Add nullable if you want to allow null values
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('show_power_bi', function (Blueprint $table) {
            // Drop the deleted_at column if the migration is rolled back
            $table->dropColumn('deleted_at');
        });
    }
};
