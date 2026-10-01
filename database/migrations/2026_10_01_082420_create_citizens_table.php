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
        Schema::create('citizens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_card_id')->constrained('family_cards')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('nik'); // encrypted
            $table->char('nik_hash', 64)->unique();
            $table->string('full_name', 150);
            $table->string('gender', 20);
            $table->string('place_of_birth', 100);
            $table->date('date_of_birth');
            $table->string('religion', 50)->nullable();
            $table->string('blood_type', 5)->nullable();
            $table->string('occupation', 120)->nullable();
            $table->string('phone', 25)->nullable();
            $table->string('email', 190)->nullable();
            $table->string('status_warga', 30)->default('tetap');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->index('nik_hash');
            $table->index('status_warga');
            $table->index('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('citizens');
    }
};
