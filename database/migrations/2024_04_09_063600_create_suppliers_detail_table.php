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
        Schema::create('supplier_contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->unsignedBigInteger('department_id');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->tinyInteger('main')->default(0)->comment("1 => main");
            $table->tinyInteger('status')->default(0)->comment("1 => Active, 0 => De-Active");
            $table->timestamps();

            /** Foreign key define here */
            $table->foreign('supplier')->references('id')->on('suppliers');
            $table->foreign('department_id')->references('id')->on('department');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_contacts');
    }
};
