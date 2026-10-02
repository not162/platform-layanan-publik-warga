<?php

namespace App\Http\Resources\V1;

use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Purchase
 */
class PurchaseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'vendor_name' => $this->vendor_name,
            'purchase_date' => $this->purchase_date?->toDateString(),
            'invoice_number' => $this->invoice_number,
            'purpose' => $this->purpose,
            'notes' => $this->notes,
            'receipt_url' => $this->receipt_path ? asset('storage/'.$this->receipt_path) : null,
            'total_amount_idr' => $this->calculateTotalIdr(),
            'finance_transaction_id' => $this->finance_transaction_id,
            'transaction_number' => $this->transaction?->transaction_number,
            'created_by_name' => $this->creator?->name,
            'version' => $this->version,
            'items' => PurchaseItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
