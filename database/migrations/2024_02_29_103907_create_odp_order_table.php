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
        Schema::create('odp_order', function (Blueprint $table) {
            $table->id();
            $table->string('gp_id')->nullable();
            $table->string('gp_name')->nullable();
            $table->string('parent_id')->nullable();
            $table->string('parent_name')->nullable();
            $table->string('account_id')->nullable();
            $table->string('account_name')->nullable();
            $table->string('year_01')->nullable();
            $table->string('year_02')->nullable();
            $table->string('year_03')->nullable();
            $table->string('year_04')->nullable();
            $table->string('year_05')->nullable();
            $table->string('year_06')->nullable();
            $table->string('year_07')->nullable();
            $table->string('year_08')->nullable();
            $table->string('year_09')->nullable();
            $table->string('year_10')->nullable();
            $table->string('year_11')->nullable();
            $table->string('year_12')->nullable();
            $table->string('year_13')->nullable();
            $table->string('year_14')->nullable();
            $table->string('year_15')->nullable();
            $table->string('year_16')->nullable();
            $table->string('year_17')->nullable();
            $table->string('year_18')->nullable();
            $table->string('year_19')->nullable();
            $table->string('year_20')->nullable();
            $table->string('year_21')->nullable();
            $table->string('year_22')->nullable();
            $table->string('year_23')->nullable();
            $table->string('year_24')->nullable();
            $table->string('year_25')->nullable();
            $table->string('year_26')->nullable();
            $table->string('year_27')->nullable();
            $table->string('year_28')->nullable();
            $table->string('year_29')->nullable();
            $table->string('year_30')->nullable();
            $table->string('year_31')->nullable();
            $table->string('year_32')->nullable();
            $table->string('year_33')->nullable();
            $table->string('year_34')->nullable();
            $table->string('year_35')->nullable();
            $table->string('year_36')->nullable();
            $table->string('year_37')->nullable();
            $table->string('year_38')->nullable();
            $table->string('year_39')->nullable();
            $table->string('year_40')->nullable();
            $table->string('year_41')->nullable();
            $table->string('year_42')->nullable();
            $table->string('year_43')->nullable();
            $table->string('year_44')->nullable();
            $table->string('year_45')->nullable();
            $table->string('year_46')->nullable();
            $table->string('year_47')->nullable();
            $table->string('year_48')->nullable();
            $table->string('year_49')->nullable();
            $table->string('year_50')->nullable();
            $table->string('year_51')->nullable();
            $table->string('year_52')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('odp_order');
    }
};
