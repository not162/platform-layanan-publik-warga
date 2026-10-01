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
        Schema::create('letters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('citizen_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('status')->default('draft'); // draft, submitted, verified, approved, completed, rejected
            $table->string('attachment_path')->nullable();
            $table->string('ticket_number')->nullable()->unique();
            $table->string('letter_number')->nullable()->unique();
            $table->integer('version')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('letters');
    }
};
