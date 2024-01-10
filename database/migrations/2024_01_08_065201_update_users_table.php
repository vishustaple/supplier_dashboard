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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('user_type')->default(1)->comment("1 => admin, 2 => supplier")->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         /** Revert the changes if needed */
         Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('user_type');
            $table->enum('user_type', ['admin', 'supplier'])->default('admin')->comment("admin => it will be admin")->after('password');
        });
    }
};
