<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\FinancialReportResource;
use App\Models\FinancialReport;
use App\Services\FinancialReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FinancialReportController extends Controller
{
    public function __construct(protected FinancialReportService $reportService) {}

    /**
     * Warga / Publik: Daftar laporan keuangan triwulan resmi yang telah dipublikasikan.
     */
    public function publicReports(Request $request): AnonymousResourceCollection
    {
        $reports = $this->reportService->listReports(true, (int) $request->query('per_page', 10));

        return FinancialReportResource::collection($reports);
    }

    /**
     * Admin / Pengurus: Daftar seluruh laporan triwulan (termasuk draf dan revisi).
     */
    public function adminIndex(Request $request): AnonymousResourceCollection
    {
        $this->authorizeReportAccess($request, 'finance.report.read');

        $reports = $this->reportService->listReports(false, (int) $request->query('per_page', 15));

        return FinancialReportResource::collection($reports);
    }

    /**
     * Admin / Bendahara: Susun laporan triwulan baru atau buat revisi berkas berjalan.
     */
    public function generate(Request $request): JsonResponse
    {
        $this->authorizeReportAccess($request, 'finance.report.generate');

        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'quarter' => ['required', 'integer', 'min:1', 'max:4'],
        ]);

        $actor = $request->user();
        if (! $actor) {
            abort(401, 'Unauthenticated.');
        }

        $report = $this->reportService->generateQuarterlyReport(
            (int) $validated['year'],
            (int) $validated['quarter'],
            $actor
        );

        return (new FinancialReportResource($report))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Ketua RT / Bendahara: Publikasikan laporan triwulan resmi lengkap dengan seal integritas SHA-256.
     */
    public function publish(Request $request, int $id): JsonResponse
    {
        $this->authorizeReportAccess($request, 'finance.report.publish');

        $publisher = $request->user();
        if (! $publisher) {
            abort(401, 'Unauthenticated.');
        }

        /** @var FinancialReport $report */
        $report = FinancialReport::query()->findOrFail($id);

        $publishedReport = $this->reportService->publishQuarterlyReport($report, $publisher);

        return (new FinancialReportResource($publishedReport))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Otorisasi hak akses laporan keuangan RT.
     */
    protected function authorizeReportAccess(Request $request, string $permission): void
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        if (
            $user->isSuperadmin() ||
            $user->hasPermission($permission) ||
            ($permission === 'finance.report.read' && $user->hasPermission('finance.manage'))
        ) {
            return;
        }

        abort(403, 'Akses tidak diizinkan. Membutuhkan wewenang laporan keuangan RT.');
    }
}
