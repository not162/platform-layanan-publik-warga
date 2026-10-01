<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RtOfficerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'position' => $this->position,
            'phone' => $this->phone,
            'order_index' => $this->order_index,
            'photo_url' => $this->photo_url,
            'is_active' => $this->is_active,
        ];
    }
}
