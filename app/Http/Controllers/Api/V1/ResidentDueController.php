<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\DuePaymentResource;
use App\Http\Resources\V1\ResidentDueResource;
use App\Models\DuePayment;
use App\Models\ResidentDue;
use App\Services\ResidentDueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ResidentDueController extends Controller
{
    public function __construct(protected ResidentDueService $residentDueService) {}

    /**
     * Warga: Lihat daftar tagihan iuran mandiri.
     */
    public function meDues(Request $request): AnonymousResourceCollection
    {
        $citizen = $request->user()?->citizen;

        if (! $citizen) {
            abort(404, 'Data kependudukan tidak ditemukan untuk akun ini.');
        }

        $year = $request->query('year') ? (int) $request->query('year') : null;
        $status = (string) $request->query('status', '');

        $dues = $this->residentDueService->getCitizenDues(
            $citizen,
            $year,
            $status !== '' ? $status : null,
            (int) $request->query('per_page', 15)
        );

        return ResidentDueResource::collection($dues);
    }

    /**
     * Warga: Rincian satu tagihan iuran mandiri.
     */
    public function meDueDetail(Request $request, string|int $id): ResidentDueResource
    {
        $citizen = $request->user()?->citizen;

        if (! $citizen) {
            abort(404, 'Data kependudukan tidak ditemukan.');
        }

        $due = ResidentDue::query()
            ->where('citizen_id', '=', $citizen->id, 'and')
            ->with(['payments.receiver', 'citizen'])
            ->findOrFail($id);

        return new ResidentDueResource($due);
    }

    /**
     * Warga: Riwayat pembayaran iuran mandiri.
     */
    public function mePayments(Request $request): AnonymousResourceCollection
    {
        $citizen = $request->user()?->citizen;

        if (! $citizen) {
            abort(404, 'Data kependudukan tidak ditemukan.');
        }

        $year = $request->query('year') ? (int) $request->query('year') : null;
        $payments = $this->residentDueService->getCitizenPayments(
            $citizen,
            $year,
            (int) $request->query('per_page', 15)
        );

        return DuePaymentResource::collection($payments);
    }

    /**
     * Warga: Ringkasan total tagihan, pembayaran, dan tunggakan iuran kas.
     */
    public function meFinanceSummary(Request $request): JsonResponse
    {
        $citizen = $request->user()?->citizen;

        if (! $citizen) {
            abort(404, 'Data kependudukan tidak ditemukan.');
        }

        $summary = $this->residentDueService->getPersonalSummary($citizen);

        return response()->json([
            'data' => $summary,
            'message' => 'Ringkasan keuangan warga berhasil dimuat.',
        ]);
    }

    /**
     * Admin / Bendahara: Daftar seluruh tagihan iuran warga RT.
     */
    public function adminDues(Request $request): AnonymousResourceCollection
    {
        $this->authorizeAdminAccess($request, 'finance.dues.read');

        $query = ResidentDue::query()->with(['citizen', 'payments.receiver']);

        if ($request->filled('year')) {
            $query->where('period_year', '=', (int) $request->query('year'), 'and');
        }

        if ($request->filled('month')) {
            $query->where('period_month', '=', (int) $request->query('month'), 'and');
        }

        if ($request->filled('status')) {
            $query->where('status', '=', strtoupper((string) $request->query('status')), 'and');
        }

        if ($request->filled('citizen_id')) {
            $query->where('citizen_id', '=', (int) $request->query('citizen_id'), 'and');
        }

        $dues = $query->orderBy('period_year', 'desc')
            ->orderBy('period_month', 'desc')
            ->paginate((int) $request->query('per_page', 20));

        return ResidentDueResource::collection($dues);
    }

    /**
     * Admin / Bendahara: Buat penagihan iuran bulanan untuk seluruh warga aktif secara massal.
     */
    public function adminGenerateDues(Request $request): JsonResponse
    {
        $this->authorizeAdminAccess($request, 'finance.dues.manage');

        $validated = $request->validate([
            'period_year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'period_month' => ['required', 'integer', 'min:1', 'max:12'],
            'amount_due_idr' => ['required', 'integer', 'min:1000'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $count = $this->residentDueService->generateMonthlyDues(
            (int) $validated['period_year'],
            (int) $validated['period_month'],
            (int) $validated['amount_due_idr'],
            $validated['due_date'] ?? null,
            $validated['notes'] ?? null
        );

        return response()->json([
            'message' => "Berhasil menerbitkan {$count} tagihan iuran kas warga untuk periode {$validated['period_month']}/{$validated['period_year']}.",
            'generated_count' => $count,
        ], 201);
    }

    /**
     * Admin / Bendahara: Daftar seluruh transaksi pembayaran iuran kas.
     */
    public function adminPayments(Request $request): AnonymousResourceCollection
    {
        $this->authorizeAdminAccess($request, 'finance.payment.read');

        $query = DuePayment::query()->with(['due.citizen', 'receiver']);

        if ($request->filled('status')) {
            $query->where('status', '=', strtoupper((string) $request->query('status')), 'and');
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', '=', (string) $request->query('payment_method'), 'and');
        }

        $payments = $query->orderBy('paid_at', 'desc')
            ->paginate((int) $request->query('per_page', 20));

        return DuePaymentResource::collection($payments);
    }

    /**
     * Admin / Bendahara: Catat penerimaan pembayaran iuran kas warga.
     */
    public function adminRecordPayment(Request $request): JsonResponse
    {
        $this->authorizeAdminAccess($request, 'finance.payment.create');

        $validated = $request->validate([
            'resident_due_id' => ['required', 'integer', 'exists:resident_dues,id'],
            'amount_paid_idr' => ['required', 'integer', 'min:1'],
            'payment_method' => ['required', 'string', 'in:cash,transfer,qris,other'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $due = ResidentDue::query()->findOrFail($validated['resident_due_id']);

        $payment = $this->residentDueService->recordPayment(
            $due,
            (int) $validated['amount_paid_idr'],
            $validated['payment_method'],
            $request->user(),
            $validated['notes'] ?? null
        );

        return (new DuePaymentResource($payment->load(['due.citizen', 'receiver'])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Otorisasi hak akses kepengurusan keuangan RT.
     */
    protected function authorizeAdminAccess(Request $request, string $permission): void
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        if (
            $user->isSuperadmin() ||
            $user->isBendahara() ||
            $user->isKetuaRt() ||
            $user->isAdmin() ||
            $user->hasPermission($permission)
        ) {
            return;
        }

        abort(403, 'Akses tidak diizinkan. Membutuhkan wewenang kepengurusan keuangan RT.');
    }
}
