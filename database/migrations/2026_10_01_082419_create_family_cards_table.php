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
        Schema::create('family_cards', function (Blueprint $table) {
            $table->id();
            $table->text('no_kk'); // encrypted
            $table->char('no_kk_hash', 64)->unique();
            $table->string('address', 255);
            $table->string('rt', 3)->default('001');
            $table->string('rw', 3)->default('001');
            $table->string('province', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('village', 100)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->index('no_kk_hash');
            $table->index(['rt', 'rw']);
            $table->index('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('family_cards');
    }
};
