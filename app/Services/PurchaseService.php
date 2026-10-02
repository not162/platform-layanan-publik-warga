<?php

namespace App\Services;

use App\Models\FinanceTransaction;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseService
{
    public function __construct(protected AuditService $auditService) {}

    /**
     * Catat pembelian/pengadaan belanja RT lengkap dengan itemisasi dan bukti nota.
     *
     * @param  array{
     *     vendor_name: string,
     *     purchase_date: string,
     *     invoice_number?: ?string,
     *     purpose: string,
     *     notes?: ?string,
     *     items: array<int, array{
     *         item_name: string,
     *         description?: ?string,
     *         quantity: int,
     *         unit?: ?string,
     *         unit_price_idr: int
     *     }>
     * }  $data
     */
    public function recordPurchase(array $data, ?UploadedFile $receipt, User $creator): Purchase
    {
        if (empty($data['items'])) {
            abort(422, 'Rincian belanja minimal harus memiliki 1 item barang atau jasa.');
        }

        return DB::transaction(function () use ($data, $receipt, $creator) {
            $receiptPath = null;
            if ($receipt) {
                $filename = 'receipt-'.date('Ymd').'-'.Str::random(12).'.'.$receipt->getClientOriginalExtension();
                $receiptPath = $receipt->storeAs('receipts', $filename, 'public');
            }

            /** @var Purchase $purchase */
            $purchase = Purchase::query()->create([
                'vendor_name' => $data['vendor_name'],
                'purchase_date' => $data['purchase_date'],
                'invoice_number' => $data['invoice_number'] ?? null,
                'purpose' => $data['purpose'],
                'notes' => $data['notes'] ?? null,
                'receipt_path' => $receiptPath,
                'created_by' => $creator->id,
                'version' => 1,
            ]);

            $totalExpenditureIdr = 0;

            foreach ($data['items'] as $item) {
                $quantity = max(1, (int) $item['quantity']);
                $unitPrice = max(0, (int) $item['unit_price_idr']);
                $subtotal = $quantity * $unitPrice;
                $totalExpenditureIdr += $subtotal;

                PurchaseItem::query()->create([
                    'purchase_id' => $purchase->id,
                    'item_name' => $item['item_name'],
                    'description' => $item['description'] ?? null,
                    'quantity' => $quantity,
                    'unit' => $item['unit'] ?? 'pcs',
                    'unit_price_idr' => $unitPrice,
                    'subtotal_idr' => $subtotal,
                ]);
            }

            // Integrasi ke Buku Kas Umum (FinanceTransaction) untuk menjaga integritas pembukuan
            $txNumber = 'TX-EXP-'.date('Ymd').'-'.strtoupper(Str::random(6));

            $transaction = FinanceTransaction::query()->create([
                'transaction_number' => $txNumber,
                'source' => 'Pengeluaran Belanja RT',
                'amount_idr' => $totalExpenditureIdr,
                'created_by' => $creator->id,
                'category' => 'Belanja & Pengadaan Lingkungan',
                'type' => 'expense',
                'amount' => $totalExpenditureIdr,
                'description' => "Pengeluaran belanja {$purchase->purpose} kepada {$purchase->vendor_name} (Faktur: ".($purchase->invoice_number ?? '-').')',
                'transaction_date' => $purchase->purchase_date,
                'status' => 'published',
                'version' => 1,
            ]);

            $purchase->finance_transaction_id = $transaction->id;
            $purchase->save();

            $this->auditService->log(
                'purchase.created',
                Purchase::class,
                $purchase->id,
                null,
                [
                    'vendor_name' => $purchase->vendor_name,
                    'total_idr' => $totalExpenditureIdr,
                    'item_count' => count($data['items']),
                ]
            );

            return $purchase->load(['items', 'transaction', 'creator']);
        });
    }

    /**
     * Daftar seluruh riwayat belanja lingkungan RT.
     *
     * @param  array<string, mixed>  $filters
     */
    public function listPurchases(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Purchase::query()->with(['items', 'transaction', 'creator']);

        if (! empty($filters['vendor_name'])) {
            $query->where('vendor_name', 'like', '%'.$filters['vendor_name'].'%', 'and');
        }

        if (! empty($filters['start_date'])) {
            $query->where('purchase_date', '>=', $filters['start_date'], 'and');
        }

        if (! empty($filters['end_date'])) {
            $query->where('purchase_date', '<=', $filters['end_date'], 'and');
        }

        return $query->orderBy('purchase_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }

    /**
     * Rincian satu berkas belanja beserta seluruh item pengadaan.
     */
    public function getPurchaseDetail(int $id): Purchase
    {
        return Purchase::query()
            ->with(['items', 'transaction', 'creator'])
            ->findOrFail($id);
    }
}
