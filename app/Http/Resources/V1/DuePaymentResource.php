<?php

namespace App\Http\Resources\V1;

use App\Models\DuePayment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DuePayment
 */
class DuePaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'resident_due_id' => $this->resident_due_id,
            'receipt_number' => $this->receipt_number,
            'amount_paid_idr' => (int) $this->amount_paid_idr,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'payment_method' => $this->payment_method,
            'proof_path' => $this->proof_path,
            'status' => $this->status,
            'notes' => $this->notes,
            'receiver_name' => $this->receiver?->name,
            'due' => new ResidentDueResource($this->whenLoaded('due')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
