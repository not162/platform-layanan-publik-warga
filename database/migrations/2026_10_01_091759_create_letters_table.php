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
            $table->foreignId('citizen_id')->constrained('citizens')->cascadeOnDelete();
            $table->foreignId('jenis_surat_id')->nullable()->constrained('jenis_surat')->nullOnDelete();
            $table->string('type', 100);
            $table->string('ticket_number', 32)->unique();
            $table->string('keperluan', 500)->nullable();
            $table->json('data_tambahan')->nullable();
            $table->string('status', 30)->default('draft'); // draft, submitted, verified, approved, completed, rejected
            $table->string('letter_number', 100)->nullable()->unique();
            $table->text('catatan_admin')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('verification_token', 64)->nullable()->unique();
            $table->string('file_path', 255)->nullable();
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
        Schema::dropIfExists('letters');
    }
};
