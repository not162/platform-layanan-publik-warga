<?php

namespace App\Http\Resources\V1;

use App\Models\FinanceTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin FinanceTransaction
 */
class FinanceTransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Endpoint publik/warga tidak boleh menampilkan nama pembayar iuran.
        $isFinanceStaff = (bool) $request->user()?->hasPermission('finance.transaction.read');

        return [
            'id' => $this->id,
            'transaction_number' => $this->transaction_number,
            'type' => $this->type,
            'source' => $this->source ?? 'other',
            'category' => $this->category,
            'amount' => $this->amount,
            'amount_idr' => $this->amount_idr ?: (int) round((float) $this->amount),
            'description' => $isFinanceStaff ? $this->description : $this->publicDescription(),
            'transaction_date' => $this->transaction_date?->format('Y-m-d'),
            'status' => $this->status,
            'published_at' => $this->published_at?->toIso8601String(),
            'original_transaction_id' => $this->original_transaction_id,
            'reversal_reason' => $this->reversal_reason,
            'is_reversal' => $this->isReversal(),
            'is_system' => $this->when($isFinanceStaff, fn () => $this->isSystem()),
            'version' => $this->version,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
