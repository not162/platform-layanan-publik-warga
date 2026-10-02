<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\PurchaseResource;
use App\Services\PurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PurchaseController extends Controller
{
    public function __construct(protected PurchaseService $purchaseService) {}

    /**
     * Daftar seluruh riwayat belanja lingkungan RT.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizeFinanceAccess($request, 'finance.purchase.read');

        $filters = [
            'vendor_name' => $request->query('vendor_name'),
            'start_date' => $request->query('start_date'),
            'end_date' => $request->query('end_date'),
        ];

        $purchases = $this->purchaseService->listPurchases($filters, (int) $request->query('per_page', 15));

        return PurchaseResource::collection($purchases);
    }

    /**
     * Catat berkas belanja baru dengan itemisasi rincian dan unggah bukti nota.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorizeFinanceAccess($request, 'finance.purchase.create');

        $validated = $request->validate([
            'vendor_name' => ['required', 'string', 'max:150'],
            'purchase_date' => ['required', 'date'],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'purpose' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'receipt' => ['nullable', 'file', 'mimes:jpeg,png,jpg,gif,svg,webp,bmp,pdf', 'max:10240'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_name' => ['required', 'string', 'max:150'],
            'items.*.description' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit' => ['nullable', 'string', 'max:30'],
            'items.*.unit_price_idr' => ['required', 'integer', 'min:0'],
        ]);

        $receipt = $request->file('receipt');
        $creator = $request->user();

        if (! $creator) {
            abort(401, 'Unauthenticated.');
        }

        $purchase = $this->purchaseService->recordPurchase($validated, $receipt, $creator);

        return (new PurchaseResource($purchase))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Tampilkan rincian satu berkas belanja beserta seluruh item pengadaan.
     */
    public function show(Request $request, int $id): PurchaseResource
    {
        $this->authorizeFinanceAccess($request, 'finance.purchase.read');

        $purchase = $this->purchaseService->getPurchaseDetail($id);

        return new PurchaseResource($purchase);
    }

    /**
     * Otorisasi hak akses kepengurusan keuangan RT.
     */
    protected function authorizeFinanceAccess(Request $request, string $permission): void
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
            $user->hasPermission($permission) ||
            $user->hasPermission('finance.manage')
        ) {
            return;
        }

        abort(403, 'Akses tidak diizinkan. Membutuhkan wewenang pengadaan atau keuangan RT.');
    }
}
