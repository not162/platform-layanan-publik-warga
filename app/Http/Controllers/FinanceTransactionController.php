<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFinanceTransactionRequest;
use App\Http\Requests\UpdateFinanceTransactionRequest;
use App\Http\Resources\V1\FinanceTransactionResource;
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

        $summary = $this->financeService->getPublicSummary($year, $month);

        return response()->json([
            'data' => [
                'total_income' => $summary['total_income'],
                'total_expense' => $summary['total_expense'],
                'net_balance' => $summary['net_balance'],
                'transactions' => FinanceTransactionResource::collection($summary['transactions']),
            ],
        ]);
    }

    public function store(StoreFinanceTransactionRequest $request): FinanceTransactionResource
    {
        $transaction = $this->financeService->create($request->validated(), $request->user());

        return new FinanceTransactionResource($transaction);
    }

    public function update(UpdateFinanceTransactionRequest $request, int|string $id): FinanceTransactionResource
    {
        $transaction = FinanceTransaction::query()->findOrFail($id);
        $data = $request->validated();

        if ($data['action'] === 'publish') {
            $transaction = $this->financeService->publish($transaction, (int) $data['version']);
        } elseif ($data['action'] === 'reverse') {
            $transaction = $this->financeService->reverse($transaction, (int) $data['version'], (string) $data['reason']);
        }

        return new FinanceTransactionResource($transaction);
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

        $report = $this->financeReportService->generateMonthlyReport($year, $month, $format);

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

        $report = $this->financeReportService->generateCitizenDuesReport($year, $month);

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
            $user->isBendahara() ||
            $user->isSekretaris() ||
            $user->isKetuaRt() ||
            $user->hasPermission('finance.report') ||
            $user->hasPermission('finance.read')
        ) {
            return;
        }

        abort(403, 'Akses tidak diizinkan. Hanya peran Bendahara, Sekretaris, atau Ketua RT yang dapat mengakses laporan dan backup keuangan.');
    }
}
