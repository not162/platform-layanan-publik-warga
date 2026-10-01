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
        Schema::create('community_events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('location');
            $table->date('event_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->boolean('is_published')->default(true);
            $table->integer('version')->default(1);
            $table->timestamps();

            $table->index(['is_published', 'event_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('community_events');
    }
};
