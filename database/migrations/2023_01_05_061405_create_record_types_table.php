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
        // Schema::create('record_types', function (Blueprint $table) {
        //     $table->id();
        //     $table->string('account_name', 255);
        //     $table->unsignedBigInteger('created_by');
        //     $table->timestamps();

        //     $table->foreign('created_by')->references('id')->on('users'); /** Assuming 'users' table exists */
        //     /** Add other foreign key constraints if needed */
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Schema::dropIfExists('record_types');
    }
};
