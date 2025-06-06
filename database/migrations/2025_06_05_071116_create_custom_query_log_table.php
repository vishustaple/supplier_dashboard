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
        Schema::create('custom_query_log', function (Blueprint $table) {
            $table->id();
            $table->dateTime('event_time');
            $table->string('user_host');
            $table->longText('argument');
            $table->enum('query_type', ['INSERT', 'SELECT', 'UPDATE', 'DELETE']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_query_log');
    }
};
