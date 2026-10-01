<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SecurityReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $isStaff = $user?->hasPermission('security.manage') || $user?->hasPermission('security.read') || $user?->isSuperadmin();
        $isOwner = $user && $this->reporter_user_id === $user->id;

        $reporterName = null;
        if (! $this->is_anonymous || $isStaff || $isOwner) {
            $reporterName = $this->citizen?->full_name ?? ($this->reporter?->name ?? 'Warga RT 01');
        } else {
            $reporterName = 'Warga (Anonim)';
        }

        $emergencyNotice = null;
        if ($this->severity === 'emergency') {
            $emergencyNotice = [
                'disclaimer' => 'Laporan ini adalah koordinasi keamanan internal RT dan BUKAN pengganti panggilan darurat resmi.',
                'contacts' => [
                    'polisi' => '110',
                    'ambulans' => '118 / 119',
                    'pemadam_kebakaran' => '113',
                    'layanan_darurat_terpadu' => '112',
                ],
            ];
        }

        return [
            'id' => $this->id,
            'ticket_number' => $this->ticket_number,
            'category' => $this->category,
            'severity' => $this->severity,
            'title' => $this->title,
            'description' => $this->description,
            'location' => $this->location,
            'incident_at' => $this->incident_at?->toIso8601String(),
            'is_anonymous' => (bool) $this->is_anonymous,
            'status' => $this->status,
            'reporter' => $reporterName,
            'assigned_to' => $this->assignee?->name,
            'resolution' => $this->resolution,
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'version' => $this->version,
            'emergency_notice' => $emergencyNotice,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
