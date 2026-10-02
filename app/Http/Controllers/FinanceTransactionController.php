<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFinanceTransactionRequest;
use App\Http\Requests\UpdateFinanceTransactionRequest;
use App\Http\Resources\V1\FinanceTransactionResource;
use App\Models\DownloadAudit;
use App\Models\FinanceTransaction;
use App\Services\FinanceReportService;
use App\Services\FinanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class FinanceTransactionController extends Controller
{
    public function __construct(
        protected FinanceService $financeService,
        protected FinanceReportService $financeReportService
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $transactions = FinanceTransaction::query()
            ->latest('transaction_date')
            ->paginate(15);

        return FinanceTransactionResource::collection($transactions);
    }

    public function publicIndex(): AnonymousResourceCollection
    {
        $transactions = FinanceTransaction::query()
            ->where('status', 'published')
            ->latest('transaction_date')
            ->paginate(15);

        return FinanceTransactionResource::collection($transactions);
    }

    public function publicSummary(Request $request): JsonResponse
    {
        $year = $request->query('year') ? (int) $request->query('year') : null;
        $month = $request->query('month') ? (int) $request->query('month') : null;
        $period = (string) $request->query('period', '');
        $quarter = null;

        if ($period !== '' && preg_match('/^(\d{4})-Q([1-4])$/', $period, $matches)) {
            $year = (int) $matches[1];
            $quarter = (int) $matches[2];
        }

        // Legacy summary includes transactions
        $summary = $this->financeService->getPublicSummary($year, $month, $quarter, true);

        return response()->json([
            'data' => [
                'total_income' => $summary['total_income'],
                'total_expense' => $summary['total_expense'],
                'net_balance' => $summary['net_balance'],
                'total_income_idr' => $summary['total_income_idr'],
                'total_expense_idr' => $summary['total_expense_idr'],
                'net_balance_idr' => $summary['net_balance_idr'],
                'total_transactions' => $summary['total_transactions'],
                'transactions' => FinanceTransactionResource::collection($summary['transactions']),
            ],
        ]);
    }

    /**
     * V1.1 Public Finance Summary without returning heavy transaction list
     */
    public function publicSummaryV11(Request $request): JsonResponse
    {
        $year = $request->query('year') ? (int) $request->query('year') : null;
        $month = $request->query('month') ? (int) $request->query('month') : null;
        $period = (string) $request->query('period', '');
        $quarter = null;

        if ($period !== '' && preg_match('/^(\d{4})-Q([1-4])$/', $period, $matches)) {
            $year = (int) $matches[1];
            $quarter = (int) $matches[2];
        }

        $summary = $this->financeService->getPublicSummary($year, $month, $quarter, false);

        return response()->json([
            'data' => [
                'period' => $period !== '' ? $period : ($year ? ($month ? "{$year}-{$month}" : (string) $year) : 'all'),
                'total_income' => $summary['total_income'],
                'total_expense' => $summary['total_expense'],
                'net_balance' => $summary['net_balance'],
                'total_income_idr' => $summary['total_income_idr'],
                'total_expense_idr' => $summary['total_expense_idr'],
                'net_balance_idr' => $summary['net_balance_idr'],
                'total_transactions' => $summary['total_transactions'],
            ],
        ]);
    }

    /**
     * V1.1 Public Finance Transactions with cursor pagination and explicit select columns
     */
    public function publicTransactions(Request $request): AnonymousResourceCollection
    {
        $query = FinanceTransaction::query()
            ->where('status', 'published')
            ->select([
                'id',
                'transaction_number',
                'type',
                'source',
                'category',
                'amount',
                'amount_idr',
                'description',
                'transaction_date',
                'status',
                'published_at',
                'version',
                'created_at',
                'updated_at',
            ]);

        $period = (string) $request->query('period', '');
        if ($period !== '' && preg_match('/^(\d{4})-Q([1-4])$/', $period, $matches)) {
            $year = (int) $matches[1];
            $quarter = (int) $matches[2];
            $startMonth = ($quarter - 1) * 3 + 1;
            $endMonth = $startMonth + 2;
            $startDate = sprintf('%04d-%02d-01', $year, $startMonth);
            $endDate = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $year, $endMonth)));

            $query->whereBetween('transaction_date', [$startDate, $endDate]);
        } elseif ($request->filled('year')) {
            $year = (int) $request->query('year');
            if ($request->filled('month')) {
                $month = (int) $request->query('month');
                $startDate = sprintf('%04d-%02d-01', $year, $month);
                $endDate = date('Y-m-t', strtotime($startDate));
                $query->whereBetween('transaction_date', [$startDate, $endDate]);
            } else {
                $query->whereBetween('transaction_date', ["{$year}-01-01", "{$year}-12-31"]);
            }
        }

        $paginated = $query->latest('transaction_date')->cursorPaginate(15);

        return FinanceTransactionResource::collection($paginated);
    }

    public function store(StoreFinanceTransactionRequest $request): JsonResponse
    {
        $transaction = $this->financeService->create($request->validated(), $request->user());

        return (new FinanceTransactionResource($transaction))->response()->setStatusCode(201);
    }

    public function update(UpdateFinanceTransactionRequest $request, int|string $id): JsonResponse
    {
        $transaction = FinanceTransaction::query()->findOrFail($id);
        $data = $request->validated();

        if ($data['action'] === 'publish') {
            $transaction = $this->financeService->publish($transaction, (int) $data['version']);

            return (new FinanceTransactionResource($transaction))->response()->setStatusCode(200);
        }

        $reversal = $this->financeService->reverse($transaction, (int) $data['version'], (string) ($data['reason'] ?? ''));

        return (new FinanceTransactionResource($reversal))->response()->setStatusCode(201);
    }

    public function publish(Request $request, int|string $id): FinanceTransactionResource
    {
        $validated = $request->validate([
            'version' => ['required', 'integer', 'min:1'],
        ]);

        $transaction = FinanceTransaction::query()->findOrFail($id);
        $published = $this->financeService->publish($transaction, (int) $validated['version']);

        return new FinanceTransactionResource($published);
    }

    public function reverse(Request $request, int|string $id): JsonResponse
    {
        $validated = $request->validate([
            'version' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        $transaction = FinanceTransaction::query()->findOrFail($id);
        $reversal = $this->financeService->reverse($transaction, (int) $validated['version'], (string) $validated['reason']);

        return (new FinanceTransactionResource($reversal))->response()->setStatusCode(201);
    }

    public function destroy(Request $request, int|string $id): JsonResponse
    {
        if (! $request->user()?->hasPermission('finance.manage')) {
            abort(403, 'Akses tidak diizinkan.');
        }

        $transaction = FinanceTransaction::query()->findOrFail($id);
        $this->financeService->delete($transaction);

        return response()->json(['message' => 'Transaksi berhasil dihapus.'], 200);
    }

    /**
     * Download Laporan Keuangan Bulanan RT (HTML or CSV)
     */
    public function downloadMonthlyReport(Request $request): Response
    {
        $this->authorizeFinanceReportAccess($request);

        $year = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);
        $format = (string) $request->query('format', 'html');

        $hasTransactions = FinanceTransaction::query()
            ->where('status', '=', 'published', 'and')
            ->whereYear('transaction_date', $year)
            ->whereMonth('transaction_date', $month)
            ->exists();

        if (! $hasTransactions) {
            abort(404, 'Rekapitulasi tidak dapat diunduh karena belum ada data transaksi pada periode yang dipilih.');
        }

        $report = $this->financeReportService->generateMonthlyReport($year, $month, $format);

        DownloadAudit::create([
            'actor_user_id' => $request->user()->id,
            'resource_type' => 'finance_monthly_report',
            'resource_id' => "{$year}-{$month}",
            'file_type' => $format,
            'period_year' => $year,
            'period_quarter' => (int) ceil($month / 3),
            'action' => 'download',
            'result' => 'success',
            'ip_hash' => hash('sha256', (string) $request->ip()),
            'user_agent_hash' => hash('sha256', (string) $request->userAgent()),
            'created_at' => now(),
        ]);

        return response($report['content'], 200, [
            'Content-Type' => $report['mime_type'],
            'Content-Disposition' => "attachment; filename=\"{$report['download_name']}\"",
        ]);
    }

    /**
     * Download Rekapitulasi Kas Pembayaran Iuran Warga Per Bulan
     */
    public function downloadCitizenDuesReport(Request $request): Response
    {
        $this->authorizeFinanceReportAccess($request);

        $year = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);

        $hasDues = FinanceTransaction::query()
            ->where('status', '=', 'published', 'and')
            ->where('type', '=', 'income', 'and')
            ->where(function ($q) {
                $q->where('category', 'like', '%Iuran%')
                    ->orWhere('category', 'like', '%Warga%');
            })
            ->whereYear('transaction_date', $year)
            ->whereMonth('transaction_date', $month)
            ->exists();

        if (! $hasDues) {
            abort(404, 'Rekapitulasi iuran warga tidak dapat diunduh karena belum ada transaksi iuran tercatat pada periode yang dipilih.');
        }

        $report = $this->financeReportService->generateCitizenDuesReport($year, $month);

        DownloadAudit::create([
            'actor_user_id' => $request->user()->id,
            'resource_type' => 'finance_citizen_dues_report',
            'resource_id' => "{$year}-{$month}",
            'file_type' => 'html',
            'period_year' => $year,
            'period_quarter' => (int) ceil($month / 3),
            'action' => 'download',
            'result' => 'success',
            'ip_hash' => hash('sha256', (string) $request->ip()),
            'user_agent_hash' => hash('sha256', (string) $request->userAgent()),
            'created_at' => now(),
        ]);

        return response($report['content'], 200, [
            'Content-Type' => $report['mime_type'],
            'Content-Disposition' => "attachment; filename=\"{$report['download_name']}\"",
        ]);
    }

    /**
     * Create Cryptographic Backup Store for Financial Ledger Data
     */
    public function createBackupStore(Request $request): JsonResponse
    {
        $this->authorizeFinanceReportAccess($request);

        $backup = $this->financeReportService->createBackupStore($request->user());

        return response()->json([
            'message' => 'Backup store data keuangan berhasil dibuat dan diverifikasi dengan checksum SHA-256.',
            'data' => $backup,
        ], 201);
    }

    /**
     * List all available backup stores in subfolder
     */
    public function listBackups(Request $request): JsonResponse
    {
        $this->authorizeFinanceReportAccess($request);

        $backups = $this->financeReportService->listBackups();

        return response()->json([
            'data' => $backups,
        ]);
    }

    /**
     * Download a specific backup store file
     */
    public function downloadBackup(Request $request, string $filename): Response
    {
        $this->authorizeFinanceReportAccess($request);

        $cleanFilename = basename($filename);
        $path = "private/financial-reports/backups/{$cleanFilename}";
        $disk = Storage::disk('local');

        if (! $disk->exists($path)) {
            abort(404, 'File backup tidak ditemukan.');
        }

        return response($disk->get($path), 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => "attachment; filename=\"{$cleanFilename}\"",
        ]);
    }

    /**
     * Verify role/permission access: Bendahara, Sekretaris, Ketua RT, and Superadmin
     */
    protected function authorizeFinanceReportAccess(Request $request): void
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        if (
            $user->isSuperadmin() ||
            $user->isAdmin() ||
            $user->isBendahara() ||
            $user->isSekretaris() ||
            $user->isKetuaRt()
        ) {
            return;
        }

        abort(403, 'Akses tidak diizinkan. Hanya peran kepengurusan RT yang dapat mengakses laporan dan rekapitulasi kas.');
    }
}
