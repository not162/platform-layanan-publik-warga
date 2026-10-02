<?php

namespace App\Http\Resources\V1;

use App\Models\PurchaseItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PurchaseItem
 */
class PurchaseItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'item_name' => $this->item_name,
            'description' => $this->description,
            'quantity' => (int) $this->quantity,
            'unit' => $this->unit,
            'unit_price_idr' => (int) $this->unit_price_idr,
            'subtotal_idr' => (int) $this->subtotal_idr,
        ];
    }
}
