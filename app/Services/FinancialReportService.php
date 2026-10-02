<?php

namespace App\Services;

use App\Models\DuePayment;
use App\Models\FinanceTransaction;
use App\Models\FinancialReport;
use App\Models\ResidentDue;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class FinancialReportService
{
    public function __construct(protected AuditService $auditService) {}

    /**
     * Susun atau buat draf laporan keuangan triwulan teragregasi.
     */
    public function generateQuarterlyReport(int $year, int $quarter, User $actor): FinancialReport
    {
        if ($quarter < 1 || $quarter > 4) {
            abort(422, 'Kuartal harus bernilai antara 1 sampai 4.');
        }

        $dates = $this->getQuarterDates($year, $quarter);
        $startDate = $dates['start'];
        $endDate = $dates['end'];

        return DB::transaction(function () use ($year, $quarter, $startDate, $endDate) {
            // Saldo awal: seluruh pemasukan dikurangi pengeluaran sebelum periode kuartal ini
            $priorIncome = (int) FinanceTransaction::query()
                ->where('status', '=', 'published', 'and')
                ->where('type', '=', 'income', 'and')
                ->where('transaction_date', '<', $startDate, 'and')
                ->sum('amount_idr');

            $priorExpense = (int) FinanceTransaction::query()
                ->where('status', '=', 'published', 'and')
                ->where('type', '=', 'expense', 'and')
                ->where('transaction_date', '<', $startDate, 'and')
                ->sum('amount_idr');

            $openingBalanceIdr = $priorIncome - $priorExpense;

            // Transaksi berjalan dalam kuartal
            $incomeIdr = (int) FinanceTransaction::query()
                ->where('status', '=', 'published', 'and')
                ->where('type', '=', 'income', 'and')
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->sum('amount_idr');

            $expenseIdr = (int) FinanceTransaction::query()
                ->where('status', '=', 'published', 'and')
                ->where('type', '=', 'expense', 'and')
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->sum('amount_idr');

            $closingBalanceIdr = $openingBalanceIdr + $incomeIdr - $expenseIdr;

            // Agregasi Iuran Warga dalam kuartal
            $quarterMonths = match ($quarter) {
                1 => [1, 2, 3],
                2 => [4, 5, 6],
                3 => [7, 8, 9],
                4 => [10, 11, 12],
            };

            $duesAssessedIdr = (int) ResidentDue::query()
                ->where('period_year', '=', $year, 'and')
                ->whereIn('period_month', $quarterMonths, 'and', false)
                ->sum('amount_due_idr');

            $duesCollectedIdr = (int) DuePayment::query()
                ->where('status', '=', 'PAID', 'and')
                ->whereBetween('paid_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
                ->sum('amount_paid_idr');

            $duesOutstandingIdr = max(0, $duesAssessedIdr - $duesCollectedIdr);

            // Cek nomor revisi jika laporan pada periode ini sudah pernah dibuat sebelumnya
            $latestRevision = (int) FinancialReport::query()
                ->where('year', '=', $year, 'and')
                ->where('quarter', '=', $quarter, 'and')
                ->max('revision');

            $newRevision = $latestRevision > 0 ? $latestRevision + 1 : 1;

            $checksum = $this->calculateChecksum(
                $year,
                $quarter,
                $newRevision,
                $openingBalanceIdr,
                $incomeIdr,
                $expenseIdr,
                $closingBalanceIdr
            );

            /** @var FinancialReport $report */
            $report = FinancialReport::query()->create([
                'year' => $year,
                'quarter' => $quarter,
                'revision' => $newRevision,
                'period_start' => $startDate,
                'period_end' => $endDate,
                'opening_balance_idr' => $openingBalanceIdr,
                'income_idr' => $incomeIdr,
                'expense_idr' => $expenseIdr,
                'closing_balance_idr' => $closingBalanceIdr,
                'dues_assessed_idr' => $duesAssessedIdr,
                'dues_collected_idr' => $duesCollectedIdr,
                'dues_outstanding_idr' => $duesOutstandingIdr,
                'status' => 'draft',
                'generated_at' => now(),
                'checksum_sha256' => $checksum,
                'version' => 1,
            ]);

            $this->auditService->log(
                'financial_report.generated',
                FinancialReport::class,
                $report->id,
                null,
                [
                    'year' => $year,
                    'quarter' => $quarter,
                    'revision' => $newRevision,
                    'closing_balance_idr' => $closingBalanceIdr,
                ]
            );

            return $report;
        });
    }

    /**
     * Publikasikan laporan triwulan resmi kepada seluruh warga.
     */
    public function publishQuarterlyReport(FinancialReport $report, User $publisher): FinancialReport
    {
        if ($report->status === 'published') {
            return $report;
        }

        $report->status = 'published';
        $report->published_at = now();
        $report->published_by = $publisher->id;
        $report->version = $report->version + 1;

        $report->checksum_sha256 = $this->calculateChecksum(
            $report->year,
            $report->quarter,
            $report->revision,
            $report->opening_balance_idr,
            $report->income_idr,
            $report->expense_idr,
            $report->closing_balance_idr
        );

        $report->save();

        $this->auditService->log(
            'financial_report.published',
            FinancialReport::class,
            $report->id,
            null,
            [
                'year' => $report->year,
                'quarter' => $report->quarter,
                'revision' => $report->revision,
                'checksum' => $report->checksum_sha256,
            ]
        );

        return $report->load('publisher');
    }

    /**
     * Ambil daftar laporan triwulan.
     */
    public function listReports(bool $publishedOnly = false, int $perPage = 15): LengthAwarePaginator
    {
        $query = FinancialReport::query()->with('publisher');

        if ($publishedOnly) {
            $query->where('status', '=', 'published', 'and');
        }

        return $query->orderBy('year', 'desc')
            ->orderBy('quarter', 'desc')
            ->orderBy('revision', 'desc')
            ->paginate($perPage);
    }

    /**
     * Hitung tanggal awal dan akhir kuartal.
     *
     * @return array{start: string, end: string}
     */
    public function getQuarterDates(int $year, int $quarter): array
    {
        return match ($quarter) {
            1 => ['start' => "{$year}-01-01", 'end' => "{$year}-03-31"],
            2 => ['start' => "{$year}-04-01", 'end' => "{$year}-06-30"],
            3 => ['start' => "{$year}-07-01", 'end' => "{$year}-09-30"],
            4 => ['start' => "{$year}-10-01", 'end' => "{$year}-12-31"],
            default => abort(422, 'Kuartal tidak valid.')
        };
    }

    /**
     * Hitung hash integritas checksum SHA-256 anti-manipulasi data.
     */
    protected function calculateChecksum(
        int $year,
        int $quarter,
        int $revision,
        int $opening,
        int $income,
        int $expense,
        int $closing
    ): string {
        $canonicalPayload = "REPORT-RT-Q{$quarter}-{$year}-REV{$revision}:OPN={$opening}:INC={$income}:EXP={$expense}:CLS={$closing}";

        return hash('sha256', $canonicalPayload);
    }
}
