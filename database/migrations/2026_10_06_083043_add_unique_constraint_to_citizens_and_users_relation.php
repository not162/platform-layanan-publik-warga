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
        Schema::table('citizens', function (Blueprint $table) {
            $table->unique('user_id', 'citizens_user_id_unique');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unique('warga_id', 'users_warga_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('citizens', function (Blueprint $table) {
            $table->dropUnique('citizens_user_id_unique');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_warga_id_unique');
        });
    }
};
