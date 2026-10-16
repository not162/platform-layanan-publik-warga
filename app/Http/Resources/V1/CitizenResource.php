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
        $plainNik = null;
        try {
            $plainNik = $this->nik ? (string) $this->nik : null;
        } catch (\Throwable) {
            $plainNik = null;
        }

        $nikMasked = $plainNik && strlen($plainNik) >= 8
            ? substr($plainNik, 0, 4).str_repeat('*', strlen($plainNik) - 8).substr($plainNik, -4)
            : '****';

        // Check if requester is authorized to view plaintext NIK (the citizen themselves or authorized officer)
        $authUser = $request->user();
        $canViewPlainNik = $authUser && (
            $authUser->id === $this->user_id
            || $authUser->isSuperadmin()
            || $authUser->isAdmin()
            || $authUser->isSekretaris()
            || $authUser->isKetuaRt()
            || $authUser->hasPermission('citizen.manage')
        );

        $address = $this->familyCard?->address;
        $dobFormatted = $this->date_of_birth?->format('Y-m-d');

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'full_name' => $this->full_name,
            'nama_lengkap' => $this->full_name,
            'nik' => $nikMasked,
            'nik_masked' => $nikMasked,
            'place_of_birth' => $this->place_of_birth,
            'tempat_lahir' => $this->place_of_birth,
            'date_of_birth' => $dobFormatted,
            'tanggal_lahir' => $dobFormatted,
            'gender' => $this->gender,
            'jenis_kelamin' => $this->gender,
            'religion' => $this->religion,
            'agama' => $this->religion,
            'occupation' => $this->occupation,
            'pekerjaan' => $this->occupation,
            'phone' => $this->phone,
            'telepon' => $this->phone,
            'phone_verified' => $this->phone_verified_at !== null,
            'phone_verified_at' => $this->phone_verified_at?->toIso8601String(),
            'email' => $this->email,
            'alamat' => $address,
            'address' => $address,
            'rt' => $this->familyCard?->rt,
            'rw' => $this->familyCard?->rw,
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
