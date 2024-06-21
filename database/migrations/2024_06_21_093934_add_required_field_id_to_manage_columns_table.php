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
        Schema::table('manage_columns', function (Blueprint $table) {
            $table->unsignedBigInteger('required_field_id')->after('required')->nullable(); // or ->after('some_column') to specify position
            $table->foreign('required_field_id')->references('id')->on('requird_fields');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('manage_columns', function (Blueprint $table) {
            $table->dropColumn('required_field_id');
        });
    }
};
