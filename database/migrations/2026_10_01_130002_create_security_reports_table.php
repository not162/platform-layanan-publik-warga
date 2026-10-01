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
        Schema::create('security_reports', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number', 32)->unique();
            $table->foreignId('citizen_id')->nullable()->constrained('citizens')->nullOnDelete();
            $table->foreignId('reporter_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category', 50);
            $table->string('severity', 20)->default('medium'); // low, medium, high, emergency
            $table->string('title', 180);
            $table->text('description');
            $table->string('location', 255);
            $table->dateTime('incident_at');
            $table->boolean('is_anonymous')->default(false);
            $table->string('status', 30)->default('submitted'); // submitted, reviewed, assigned, processing, resolved, closed, rejected
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->index('ticket_number');
            $table->index(['status', 'created_at']);
            $table->index('severity');
            $table->index('incident_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_reports');
    }
};
