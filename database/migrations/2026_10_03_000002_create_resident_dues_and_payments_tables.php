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
        Schema::create('resident_dues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('citizen_id')->constrained('citizens')->cascadeOnDelete();
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month'); // 1-12
            $table->unsignedBigInteger('amount_due_idr');
            $table->date('due_date');
            $table->string('status', 20)->default('UNPAID'); // UNPAID, PARTIAL, PAID, OVERDUE
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['citizen_id', 'period_year', 'period_month'], 'resident_dues_citizen_period_unique');
            $table->index(['period_year', 'period_month', 'status']);
        });

        Schema::create('due_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resident_due_id')->constrained('resident_dues')->cascadeOnDelete();
            $table->unsignedBigInteger('amount_paid_idr');
            $table->dateTime('paid_at');
            $table->string('payment_method', 50)->default('cash');
            $table->string('receipt_number', 50)->nullable()->unique();
            $table->string('proof_path', 500)->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('PAID');
            $table->timestamps();

            $table->index(['resident_due_id', 'status']);
            $table->index('paid_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('due_payments');
        Schema::dropIfExists('resident_dues');
    }
};
