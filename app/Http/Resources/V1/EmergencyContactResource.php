<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmergencyContactResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->category,
            'phone_number' => $this->phone_number,
            'description' => $this->description,
            'order_index' => $this->order_index,
            'is_active' => $this->is_active,
        ];
    }
}
