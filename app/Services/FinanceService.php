<?php

namespace App\Services;

use App\Models\FinanceTransaction;

class FinanceService
{
    /**
     * Create a new class instance.
     */
    public function __construct(protected AuditService $auditService) {}

    public function create(array $data): FinanceTransaction
    {
        $transaction = FinanceTransaction::create($data);

        $this->auditService->log('create', 'FinanceTransaction', $transaction->id, null, $transaction->toArray());

        return $transaction;
    }

    public function publish(FinanceTransaction $transaction, int $version): FinanceTransaction
    {
        $oldValues = $transaction->toArray();

        if ($version !== (int) $transaction->version) {
            abort(409, 'Conflict: Transaction has been modified.');
        }

        $transaction->status = 'published';
        $transaction->version = $version + 1;
        $transaction->save();

        $this->auditService->log('publish', 'FinanceTransaction', $transaction->id, $oldValues, $transaction->toArray());

        return $transaction;
    }

    /**
     * Immutable published report rule: We do not edit/delete published transactions.
     * We create a reversal transaction and mark the original as reversed.
     */
    public function reverse(FinanceTransaction $transaction, int $version, string $reason): FinanceTransaction
    {
        if ($transaction->status !== 'published') {
            abort(422, 'Only published transactions can be reversed.');
        }

        if ($version !== (int) $transaction->version) {
            abort(409, 'Conflict: Transaction has been modified.');
        }

        $oldValues = $transaction->toArray();

        // 1. Mark original as reversed
        $transaction->status = 'reversed';
        $transaction->version = $version + 1;
        $transaction->save();

        // 2. Create reversal transaction
        $reversal = FinanceTransaction::create([
            'type' => $transaction->type === 'income' ? 'expense' : 'income',
            'category' => 'Reversal: '.$transaction->category,
            'amount' => $transaction->amount,
            'description' => 'Reversal for ID '.$transaction->id.'. Reason: '.$reason,
            'transaction_date' => now()->toDateString(),
            'status' => 'published',
            'original_transaction_id' => $transaction->id,
            'version' => 1,
        ]);

        $this->auditService->log('reverse', 'FinanceTransaction', $transaction->id, $oldValues, $transaction->toArray());
        $this->auditService->log('create', 'FinanceTransaction', $reversal->id, null, $reversal->toArray());

        return $reversal;
    }
}
