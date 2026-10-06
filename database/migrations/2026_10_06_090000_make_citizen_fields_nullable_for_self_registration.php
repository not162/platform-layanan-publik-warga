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
            $table->unsignedBigInteger('family_card_id')->nullable()->change();
            $table->string('gender', 20)->nullable()->change();
            $table->string('place_of_birth', 100)->nullable()->change();
            $table->date('date_of_birth')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('citizens', function (Blueprint $table) {
            $table->unsignedBigInteger('family_card_id')->nullable(false)->change();
            $table->string('gender', 20)->nullable(false)->change();
            $table->string('place_of_birth', 100)->nullable(false)->change();
            $table->date('date_of_birth')->nullable(false)->change();
        });
    }
};
