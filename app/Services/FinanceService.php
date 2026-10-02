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
            $data['source'] = $data['source'] ?? 'other';

            // Parity: Ensure both amount and amount_idr are populated accurately
            if (isset($data['amount_idr'])) {
                $data['amount_idr'] = (int) $data['amount_idr'];
                $data['amount'] = (float) $data['amount_idr'];
            } elseif (isset($data['amount'])) {
                $data['amount_idr'] = (int) round((float) $data['amount']);
                $data['amount'] = (float) $data['amount'];
            } else {
                $data['amount_idr'] = 0;
                $data['amount'] = 0.0;
            }

            if (empty($data['transaction_number'])) {
                $prefix = date('Ym', strtotime($data['transaction_date'] ?? now()->toDateString()));
                $data['transaction_number'] = sprintf('TXN-%s-%06d', $prefix, mt_rand(100000, 999999));
            }

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
            $reversalNumber = sprintf('REV-%s-%06d', date('Ym'), $locked->id);

            $reversal = FinanceTransaction::query()->create([
                'transaction_number' => $reversalNumber,
                'type' => $locked->type === 'income' ? 'expense' : 'income',
                'source' => $locked->source ?? 'other',
                'category' => 'Jurnal Pembalik: '.$locked->category,
                'amount' => $locked->amount,
                'amount_idr' => $locked->amount_idr ?: (int) round((float) $locked->amount),
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

        if ($transaction->status === 'reversed') {
            abort(403, 'Transaksi pembalik tidak boleh dihapus.');
        }

        $oldValues = $transaction->toArray();
        $id = $transaction->id;

        FinanceTransaction::query()->whereKey($id)->delete();

        $this->auditService->log('finance.deleted', 'FinanceTransaction', $id, $oldValues, null);
        $this->clearCache();
    }

    public function getCacheKey(?int $year = null, ?int $month = null, ?int $quarter = null): string
    {
        $y = $year ?? 'all';
        $m = $month ?? 'all';
        $q = $quarter ?? 'all';

        return "public_finance_summary_{$y}_{$m}_{$q}";
    }

    public function clearCache(?int $year = null, ?int $month = null, ?int $quarter = null): void
    {
        Cache::forget($this->getCacheKey(null, null, null));
        if ($year || $month || $quarter) {
            Cache::forget($this->getCacheKey($year, $month, $quarter));
        }
        Cache::forget('public_finance_summary');
    }

    /**
     * Get aggregate summary using database SQL without loading entire collections into memory.
     */
    public function getPublicSummary(?int $year = null, ?int $month = null, ?int $quarter = null, bool $includeTransactions = false): array
    {
        $cacheKey = $this->getCacheKey($year, $month, $quarter).($includeTransactions ? '_with_txns' : '');

        return Cache::remember($cacheKey, 1800, function () use ($year, $month, $quarter, $includeTransactions) {
            $query = FinanceTransaction::query()->where('status', 'published');

            if ($year && $quarter) {
                $startMonth = ($quarter - 1) * 3 + 1;
                $endMonth = $startMonth + 2;
                $startDate = sprintf('%04d-%02d-01', $year, $startMonth);
                $endDate = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $year, $endMonth)));
                $query->whereBetween('transaction_date', [$startDate, $endDate]);
            } elseif ($year) {
                if ($month) {
                    $startDate = sprintf('%04d-%02d-01', $year, $month);
                    $endDate = date('Y-m-t', strtotime($startDate));
                    $query->whereBetween('transaction_date', [$startDate, $endDate]);
                } else {
                    $query->whereBetween('transaction_date', ["{$year}-01-01", "{$year}-12-31"]);
                }
            }

            // Aggregate directly in SQL
            $totals = (clone $query)->selectRaw("
                COALESCE(SUM(CASE WHEN type = 'income' THEN amount_idr ELSE 0 END), 0) as total_income_idr,
                COALESCE(SUM(CASE WHEN type = 'expense' THEN amount_idr ELSE 0 END), 0) as total_expense_idr,
                COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) as legacy_income,
                COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) as legacy_expense,
                COUNT(*) as total_count
            ")->first();

            $totalIncomeIdr = (int) ($totals->total_income_idr ?: round((float) ($totals->legacy_income ?? 0)));
            $totalExpenseIdr = (int) ($totals->total_expense_idr ?: round((float) ($totals->legacy_expense ?? 0)));
            $netBalanceIdr = $totalIncomeIdr - $totalExpenseIdr;

            $result = [
                'total_income' => $totalIncomeIdr,
                'total_expense' => $totalExpenseIdr,
                'net_balance' => $netBalanceIdr,
                'total_income_idr' => $totalIncomeIdr,
                'total_expense_idr' => $totalExpenseIdr,
                'net_balance_idr' => $netBalanceIdr,
                'total_transactions' => (int) ($totals->total_count ?? 0),
            ];

            if ($includeTransactions) {
                $result['transactions'] = (clone $query)->latest('transaction_date')->get();
            } else {
                $result['transactions'] = collect();
            }

            return $result;
        });
    }
}
