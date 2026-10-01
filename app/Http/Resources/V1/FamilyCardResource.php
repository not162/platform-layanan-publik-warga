<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FamilyCardResource extends JsonResource
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
            // 'no_kk' is sensitive and shouldn't be exposed by default, but if we have to, maybe partially masked.
            // But per rules, NIK/KK shouldn't be exposed on public APIs. This resource might be used for Citizen or Admin.
            // For now, return what's safe. Admin can have a separate resource or we conditionally load.
            'address' => $this->address,
            'rt' => $this->rt,
            'rw' => $this->rw,
            'province' => $this->province,
            'city' => $this->city,
            'district' => $this->district,
            'village' => $this->village,
            'version' => $this->version,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
