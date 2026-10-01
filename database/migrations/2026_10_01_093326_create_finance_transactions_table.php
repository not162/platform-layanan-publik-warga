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
        Schema::create('finance_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20); // income, expense
            $table->string('category', 100);
            $table->decimal('amount', 15, 2);
            $table->string('description', 500)->nullable();
            $table->date('transaction_date');
            $table->string('receipt_path', 500)->nullable();
            $table->string('status', 30)->default('draft'); // draft, published, reversed
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('original_transaction_id')->nullable()->constrained('finance_transactions')->nullOnDelete(); // For reversal entries
            $table->text('reversal_reason')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->index('transaction_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_transactions');
    }
};
