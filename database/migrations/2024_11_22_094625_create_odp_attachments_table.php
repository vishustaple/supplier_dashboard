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
        Schema::create('odp_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attachment_id'); /** Unsigned big integer for attachment_id */
            $table->year('year'); /** Column for storing year (4 digits) */
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('odp_attachments');
    }
};
