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
        Schema::create('download_audits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('resource_type', 80);
            $table->string('resource_id', 80);
            $table->string('file_type', 30);
            $table->unsignedSmallInteger('period_year')->nullable();
            $table->unsignedTinyInteger('period_quarter')->nullable();
            $table->string('action', 50)->default('download');
            $table->string('result', 30)->default('success'); // success, denied, failed
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent_hash', 64)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['resource_type', 'resource_id', 'created_at']);
            $table->index(['actor_user_id', 'created_at']);
            $table->index(['period_year', 'period_quarter', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('download_audits');
    }
};
