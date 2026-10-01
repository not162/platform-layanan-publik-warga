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
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number', 32)->unique();
            $table->foreignId('citizen_id')->nullable()->constrained('citizens')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('kategori', 80)->default('umum');
            $table->string('title', 180);
            $table->text('description');
            $table->string('lokasi', 255)->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->string('status', 30)->default('submitted'); // submitted, reviewed, processing, resolved, closed, rejected
            $table->string('priority', 20)->default('sedang'); // rendah, sedang, tinggi, darurat
            $table->foreignId('assigned_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('admin_response')->nullable();
            $table->string('attachment_path', 255)->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->index('ticket_number');
            $table->index(['status', 'created_at']);
            $table->index('citizen_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
