<?php

namespace App\Services;

use App\Events\DuePaymentRecorded;
use App\Models\Citizen;
use App\Models\DuePayment;
use App\Models\FinanceTransaction;
use App\Models\ResidentDue;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ResidentDueService
{
    public function __construct(protected AuditService $auditService) {}

    /**
     * Generate monthly dues for all registered citizens.
     */
    public function generateMonthlyDues(
        int $year,
        int $month,
        int $amountDueIdr,
        ?string $dueDate = null,
        ?string $notes = null
    ): int {
        if ($amountDueIdr <= 0) {
            abort(422, 'Besaran tagihan iuran harus lebih besar dari 0 Rupiah.');
        }

        $dueDateTime = $dueDate
            ? Carbon::parse($dueDate)->toDateString()
            : Carbon::create($year, $month, 10)->toDateString();

        $citizens = Citizen::query()->get();
        $generatedCount = 0;

        DB::transaction(function () use ($citizens, $year, $month, $amountDueIdr, $dueDateTime, $notes, &$generatedCount) {
            foreach ($citizens as $citizen) {
                $due = ResidentDue::query()
                    ->where('citizen_id', '=', $citizen->id, 'and')
                    ->where('period_year', '=', $year, 'and')
                    ->where('period_month', '=', $month, 'and')
                    ->first();

                if (! $due) {
                    ResidentDue::query()->create([
                        'citizen_id' => $citizen->id,
                        'period_year' => $year,
                        'period_month' => $month,
                        'amount_due_idr' => $amountDueIdr,
                        'due_date' => $dueDateTime,
                        'status' => 'UNPAID',
                        'notes' => $notes ?? 'Iuran Warga RT '.config('rt.rt')." Bulan {$month}/{$year}",
                    ]);
                    $generatedCount++;
                }
            }
        });

        return $generatedCount;
    }

    /**
     * Record a payment for a specific resident due.
     */
    public function recordPayment(
        ResidentDue $due,
        int $amountPaidIdr,
        string $paymentMethod = 'cash',
        ?User $receiver = null,
        ?string $notes = null,
        ?string $proofPath = null
    ): DuePayment {
        if ($amountPaidIdr <= 0) {
            abort(422, 'Jumlah pembayaran harus lebih besar dari 0 Rupiah.');
        }

        return DB::transaction(function () use ($due, $amountPaidIdr, $paymentMethod, $receiver, $notes, $proofPath) {
            /** @var ResidentDue $lockedDue */
            $lockedDue = ResidentDue::query()->lockForUpdate()->findOrFail($due->id);

            // Re-calculate current paid to prevent concurrent duplicate payments
            $currentPaid = (int) DuePayment::query()
                ->where('resident_due_id', '=', $lockedDue->id)
                ->where('status', '=', 'PAID')
                ->sum('amount_paid_idr');

            $remainingDue = (int) $lockedDue->amount_due_idr - $currentPaid;
            if ($remainingDue <= 0 || $lockedDue->status === 'PAID') {
                abort(422, 'Tagihan iuran kas untuk periode ini telah berstatus lunas dan tidak dapat dibayar lagi.');
            }

            if ($amountPaidIdr > $remainingDue) {
                abort(422, 'Nominal pembayaran (Rp '.number_format($amountPaidIdr, 0, ',', '.').') melebihi sisa tagihan (Rp '.number_format($remainingDue, 0, ',', '.').').');
            }

            $receiptNumber = 'RCP-'.date('Ymd').'-'.strtoupper(Str::random(6));

            /** @var DuePayment $payment */
            $payment = DuePayment::query()->create([
                'resident_due_id' => $lockedDue->id,
                'amount_paid_idr' => $amountPaidIdr,
                'paid_at' => now(),
                'payment_method' => $paymentMethod,
                'receipt_number' => $receiptNumber,
                'proof_path' => $proofPath,
                'received_by' => $receiver?->id,
                'notes' => $notes,
                'status' => 'PAID',
            ]);

            // Re-calculate total paid and update due status
            $totalPaid = $currentPaid + $amountPaidIdr;

            if ($totalPaid >= $lockedDue->amount_due_idr) {
                $newStatus = 'PAID';
            } elseif ($totalPaid > 0) {
                $newStatus = 'PARTIAL';
            } elseif (now()->toDateString() > $lockedDue->due_date->toDateString()) {
                $newStatus = 'OVERDUE';
            } else {
                $newStatus = 'UNPAID';
            }

            $lockedDue->status = $newStatus;
            $lockedDue->save();

            // Link to FinanceTransaction for general ledger integrity
            $citizenName = $lockedDue->citizen?->full_name ?? 'Warga';
            $txNumber = 'TX-DUE-'.date('Ymd').'-'.strtoupper(Str::random(6));

            $tx = FinanceTransaction::query()->create([
                'transaction_number' => $txNumber,
                'source' => 'Iuran Kas Warga',
                'amount_idr' => $amountPaidIdr,
                'created_by' => $receiver?->id,
                'category' => 'Iuran Kas Warga',
                'type' => 'income',
                'amount' => $amountPaidIdr,
                'description' => "Pembayaran iuran kas RT {$lockedDue->period_month}/{$lockedDue->period_year} oleh {$citizenName} (Kuitansi: {$receiptNumber})",
                'transaction_date' => now(),
                'status' => 'published',
                'version' => 1,
            ]);

            $payment->update([
                'finance_transaction_id' => $tx->id,
            ]);

            DuePaymentRecorded::dispatch($payment, $lockedDue);

            return $payment;
        });
    }

    /**
     * Get financial summary for an individual citizen.
     *
     * @return array<string, int>
     */
    public function getPersonalSummary(Citizen $citizen): array
    {
        $dues = ResidentDue::query()
            ->where('citizen_id', '=', $citizen->id, 'and')
            ->with(['payments'])
            ->get();

        $totalAssessedIdr = (int) $dues->sum('amount_due_idr');

        $totalPaidIdr = (int) DuePayment::query()
            ->whereHas('due', function ($q) use ($citizen) {
                $q->where('citizen_id', '=', $citizen->id);
            })
            ->where('status', '=', 'PAID', 'and')
            ->sum('amount_paid_idr');

        $totalOutstandingIdr = max(0, $totalAssessedIdr - $totalPaidIdr);

        return [
            'total_billed_idr' => $totalAssessedIdr,
            'total_assessed_idr' => $totalAssessedIdr,
            'total_paid_idr' => $totalPaidIdr,
            'total_outstanding_idr' => $totalOutstandingIdr,
            'unpaid_count' => $dues->where('status', 'UNPAID')->count(),
            'partial_count' => $dues->where('status', 'PARTIAL')->count(),
            'paid_count' => $dues->where('status', 'PAID')->count(),
            'overdue_count' => $dues->where('status', 'OVERDUE')->count(),
        ];
    }

    /**
     * Get paginated dues for a citizen.
     */
    public function getCitizenDues(Citizen $citizen, ?int $year = null, ?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = ResidentDue::query()
            ->where('citizen_id', '=', $citizen->id, 'and')
            ->with(['payments.receiver']);

        if ($year) {
            $query->where('period_year', '=', $year, 'and');
        }

        if ($status) {
            $query->where('status', '=', strtoupper($status), 'and');
        }

        return $query->orderBy('period_year', 'desc')
            ->orderBy('period_month', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get paginated payments for a citizen.
     */
    public function getCitizenPayments(Citizen $citizen, ?int $year = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = DuePayment::query()
            ->whereHas('due', function ($q) use ($citizen, $year) {
                $q->where('citizen_id', '=', $citizen->id);
                if ($year) {
                    $q->where('period_year', '=', $year);
                }
            })
            ->with(['due', 'receiver']);

        return $query->orderBy('paid_at', 'desc')->paginate($perPage);
    }
}
