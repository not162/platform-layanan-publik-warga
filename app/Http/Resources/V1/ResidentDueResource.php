<?php

namespace App\Http\Resources\V1;

use App\Models\ResidentDue;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ResidentDue
 */
class ResidentDueResource extends JsonResource
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
            'citizen_id' => $this->citizen_id,
            'citizen_name' => $this->citizen?->full_name,
            'period_year' => $this->period_year,
            'period_month' => $this->period_month,
            'period_formatted' => date('F', mktime(0, 0, 0, $this->period_month, 10)).' '.$this->period_year,
            'amount_due_idr' => (int) $this->amount_due_idr,
            'total_paid_idr' => $this->getTotalPaidIdr(),
            'remaining_due_idr' => $this->getRemainingDueIdr(),
            'due_date' => $this->due_date?->toDateString(),
            'status' => $this->status,
            'notes' => $this->notes,
            'payments' => DuePaymentResource::collection($this->whenLoaded('payments')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
