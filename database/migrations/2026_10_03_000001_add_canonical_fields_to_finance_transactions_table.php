<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('finance_transactions', function (Blueprint $table) {
            $table->string('transaction_number', 50)->nullable()->unique()->after('id');
            $table->string('source', 30)->default('other')->after('type'); // dues, purchase, other
            $table->unsignedBigInteger('amount_idr')->default(0)->after('amount');

            $table->index(['source', 'status']);
        });

        // Safe incremental backfill without dropping legacy amount
        DB::table('finance_transactions')->whereNull('transaction_number')->orderBy('id')->each(function ($txn) {
            $amountIdr = (int) round((float) ($txn->amount ?? 0));
            $txnNumber = sprintf('TXN-%s-%06d', date('Ym', strtotime($txn->transaction_date ?? now())), $txn->id);

            DB::table('finance_transactions')
                ->where('id', $txn->id)
                ->update([
                    'amount_idr' => $amountIdr,
                    'transaction_number' => $txnNumber,
                ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finance_transactions', function (Blueprint $table) {
            $table->dropIndex(['source', 'status']);
            $table->dropColumn(['transaction_number', 'source', 'amount_idr']);
        });
    }
};
