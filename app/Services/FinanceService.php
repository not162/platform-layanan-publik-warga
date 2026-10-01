<?php

namespace App\Services;

use App\Models\FinanceTransaction;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class FinanceService
{
    public function __construct(protected AuditService $auditService) {}

    public function create(array $data, ?User $user = null): FinanceTransaction
    {
        return DB::transaction(function () use ($data, $user) {
            $data['status'] = $data['status'] ?? 'draft';
            $data['version'] = 1;
            if ($user) {
                $data['created_by'] = $user->id;
            }

            $transaction = FinanceTransaction::query()->create($data);

            $this->auditService->log(
                action: 'finance.created',
                entityType: 'FinanceTransaction',
                entityId: $transaction->id,
                oldValues: null,
                newValues: $transaction->toArray()
            );

            return $transaction;
        });
    }

    public function publish(FinanceTransaction $transaction, int $version): FinanceTransaction
    {
        return DB::transaction(function () use ($transaction, $version) {
            /** @var FinanceTransaction $locked */
            $locked = FinanceTransaction::query()->lockForUpdate()->findOrFail($transaction->id);

            if ($version !== (int) $locked->version) {
                abort(409, 'Konflik data: Transaksi kas telah diubah oleh pengguna lain.');
            }

            if ($locked->status === 'published') {
                abort(409, 'Transaksi telah dipublikasikan sebelumnya.');
            }

            $oldValues = $locked->toArray();

            $locked->status = 'published';
            $locked->published_at = now();
            $locked->version = $version + 1;
            $locked->save();

            $this->auditService->log(
                action: 'finance.published',
                entityType: 'FinanceTransaction',
                entityId: $locked->id,
                oldValues: $oldValues,
                newValues: $locked->toArray()
            );

            $this->clearCache();

            return $locked;
        });
    }

    /**
     * Immutable published report rule: We do not delete published transactions.
     * We create a reversal transaction and mark the original as reversed.
     */
    public function reverse(FinanceTransaction $transaction, int $version, string $reason): FinanceTransaction
    {
        return DB::transaction(function () use ($transaction, $version, $reason) {
            /** @var FinanceTransaction $locked */
            $locked = FinanceTransaction::query()->lockForUpdate()->findOrFail($transaction->id);

            if ($locked->status !== 'published') {
                abort(422, 'Hanya transaksi kas yang telah dipublikasikan yang dapat dibatalkan (reverse).');
            }

            if ($version !== (int) $locked->version) {
                abort(409, 'Konflik data: Transaksi kas telah diubah.');
            }

            if (trim($reason) === '') {
                abort(422, 'Alasan pembatalan (reversal reason) wajib diisi.');
            }

            $oldValues = $locked->toArray();

            // 1. Mark original as reversed
            $locked->status = 'reversed';
            $locked->reversal_reason = $reason;
            $locked->version = $version + 1;
            $locked->save();

            // 2. Create reversal offsetting transaction
            $reversal = FinanceTransaction::query()->create([
                'type' => $locked->type === 'income' ? 'expense' : 'income',
                'category' => 'Jurnal Pembalik: '.$locked->category,
                'amount' => $locked->amount,
                'description' => "Pembalik transaksi #{$locked->id}. Alasan: {$reason}",
                'transaction_date' => now()->toDateString(),
                'status' => 'published',
                'created_by' => Auth::id(),
                'published_at' => now(),
                'original_transaction_id' => $locked->id,
                'version' => 1,
            ]);

            $this->auditService->log('finance.reversed', 'FinanceTransaction', $locked->id, $oldValues, $locked->toArray());
            $this->auditService->log('finance.created', 'FinanceTransaction', $reversal->id, null, $reversal->toArray());

            $this->clearCache();

            return $reversal;
        });
    }

    public function delete(FinanceTransaction $transaction): void
    {
        if ($transaction->status === 'published') {
            abort(403, 'Transaksi yang telah dipublikasikan tidak boleh dihapus. Buat transaksi pembalik (reversal).');
        }

        $oldValues = $transaction->toArray();
        $id = $transaction->id;

        FinanceTransaction::query()->whereKey($id)->delete();

        $this->auditService->log('finance.deleted', 'FinanceTransaction', $id, $oldValues, null);
        $this->clearCache();
    }

    public function getCacheKey(?int $year = null, ?int $month = null): string
    {
        $y = $year ?? 'all';
        $m = $month ?? 'all';

        return "public_finance_summary_{$y}_{$m}";
    }

    public function clearCache(?int $year = null, ?int $month = null): void
    {
        Cache::forget($this->getCacheKey(null, null));
        if ($year || $month) {
            Cache::forget($this->getCacheKey($year, $month));
        }
        Cache::forget('public_finance_summary');
    }

    public function getPublicSummary(?int $year = null, ?int $month = null): array
    {
        $cacheKey = $this->getCacheKey($year, $month);

        return Cache::remember($cacheKey, 1800, function () use ($year, $month) {
            $query = FinanceTransaction::query()->where('status', 'published');

            if ($year) {
                $query->whereYear('transaction_date', $year);
            }
            if ($month) {
                $query->whereMonth('transaction_date', $month);
            }

            $transactions = $query->latest('transaction_date')->get();

            $totalIncome = (float) $transactions->where('type', 'income')->sum('amount');
            $totalExpense = (float) $transactions->where('type', 'expense')->sum('amount');
            $balance = $totalIncome - $totalExpense;

            return [
                'total_income' => $totalIncome,
                'total_expense' => $totalExpense,
                'net_balance' => $balance,
                'transactions' => $transactions,
            ];
        });
    }
}
