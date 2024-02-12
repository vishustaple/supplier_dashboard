<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCatalogDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('catalog_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('catalog_id');
            $table->string('file_name', 512);
            $table->string('table_key', 512);
            $table->text('table_value')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('catalog_id')->references('id')->on('catalog')->onDelete('cascade');
            // Assuming 'catalog' table exists and 'id' column is the referenced key

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
        Schema::dropIfExists('catalog_details');
    }
}
