<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDeletedByToUploadedFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('uploaded_files', function (Blueprint $table) {
            // Add deleted_at column
            $table->softDeletes();
            
            // Add deleted_by column
            $table->unsignedBigInteger('deleted_by')->nullable()->after('created_by');
            $table->foreign('deleted_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('uploaded_files', function (Blueprint $table) {
            // Drop deleted_at column
            $table->dropSoftDeletes();
            
            // Drop deleted_by column
            $table->dropForeign(['deleted_by']);
            $table->dropColumn('deleted_by');
        });
    }
}

