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
        Schema::create('round_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('day_of_week'); // senin, selasa, rabu, kamis, jumat, sabtu, minggu
            $table->string('shift_name')->default('Malam (22:00 - 04:00)');
            $table->json('officer_names'); // Array of names on duty
            $table->string('pos_location')->default('Pos Ronda RT 01');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['day_of_week', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('round_schedules');
    }
};
