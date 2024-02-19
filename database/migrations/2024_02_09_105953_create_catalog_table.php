<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCatalogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('catalog', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_id');
            $table->string('sku', 255);
            $table->text('description');
            $table->string('price', 255);
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            
            $table->foreign('supplier_id')->references('id')->on('suppliers');
            // Assuming 'suppliers' table exists and 'id' column is the referenced key
            
            $table->index('sku');
            // Add other indexes if needed
            
            // Add primary key constraint
            // $table->primary('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('catalog');
    }
}