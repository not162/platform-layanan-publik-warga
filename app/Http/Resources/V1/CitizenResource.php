<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CitizenResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $nikMasked = null;
        if ($this->nik) {
            $plain = (string) $this->nik;
            $nikMasked = strlen($plain) >= 8
                ? substr($plain, 0, 4).str_repeat('*', strlen($plain) - 8).substr($plain, -4)
                : '****';
        }

        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'nik_masked' => $nikMasked,
            'place_of_birth' => $this->place_of_birth,
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'gender' => $this->gender,
            'religion' => $this->religion,
            'occupation' => $this->occupation,
            'phone' => $this->phone,
            'phone_verified' => $this->phone_verified_at !== null,
            'phone_verified_at' => $this->phone_verified_at?->toIso8601String(),
            'email' => $this->email,
            'status_warga' => $this->status_warga,
            'is_active' => (bool) $this->is_active,
            'version' => $this->version,
            'family_card' => new FamilyCardResource($this->whenLoaded('familyCard')),
            'user' => new UserResource($this->whenLoaded('user')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
