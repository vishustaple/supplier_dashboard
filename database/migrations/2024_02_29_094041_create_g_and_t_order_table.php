<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('g_and_t_laboratories_charles_river_order', function (Blueprint $table) {
            $table->id();
            $table->string('sold_to_account')->nullable();
            $table->string('sold_to_name')->nullable();
            $table->string('ship_to_account')->nullable();
            $table->string('ship_to_name')->nullable();
            $table->text('ship_to_address')->nullable();
            $table->string('categories')->nullable();
            $table->string('sub_group_1')->nullable();
            $table->string('product')->nullable();
            $table->text('description')->nullable();
            $table->string('green_y_and_n')->nullable();
            $table->string('quantity_shipped')->nullable();
            $table->string('on_core_spend')->nullable();
            $table->string('off_core_spend')->nullable();
            $table->date('date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('g_and_t_laboratories_charles_river_order');
    }
};
