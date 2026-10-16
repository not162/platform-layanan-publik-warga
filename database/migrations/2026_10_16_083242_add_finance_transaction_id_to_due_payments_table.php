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
        Schema::table('due_payments', function (Blueprint $table) {
            $table->foreignId('finance_transaction_id')
                ->nullable()
                ->after('resident_due_id')
                ->constrained('finance_transactions')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('due_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('finance_transaction_id');
        });
    }
};
