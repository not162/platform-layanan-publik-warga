<?php

namespace App\Services;

use App\Models\Letter;
use App\Models\SecurityReport;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentGeneratorService
{
    public function __construct(protected AuditService $auditService) {}

    /**
     * Generate an official letter document from its assigned template.
     * Idempotent: Does not regenerate if document is already generated for this version.
     */
    public function generateLetterDocument(Letter $letter, bool $force = false): string
    {
        $storageDisk = Storage::disk('local');

        if (! $force && $letter->file_path && $letter->document_hash && $storageDisk->exists($letter->file_path)) {
            return $letter->file_path;
        }

        $letter->loadMissing(['citizen.familyCard', 'letterType', 'approvedBy', 'verifiedBy']);

        $token = $letter->verification_token ?: Str::random(40);
        $letterNumber = $letter->letter_number ?: ('SK/'.date('Ymd').'/'.$letter->id);

        $templateKey = $letter->letterType?->template_key;
        $viewName = match ($templateKey) {
            'surat-kematian' => 'documents.templates.surat-kematian',
            'surat-pindah' => 'documents.templates.surat-pindah',
            'surat-keterangan-tidak-mampu' => 'documents.templates.surat-keterangan-tidak-mampu',
            default => 'documents.templates.surat-keterangan',
        };

        if (! view()->exists($viewName)) {
            $viewName = 'documents.templates.surat-keterangan';
        }

        $citizen = $letter->citizen;
        $familyCard = $citizen?->familyCard;

        $payload = [
            'letter_number' => $letterNumber,
            'purpose' => $letter->keperluan,
            'description' => $letter->data_tambahan['description'] ?? null,
            'data_tambahan' => $letter->data_tambahan ?? [],
            'deceased' => $letter->data_tambahan ?? [],
            'destination' => $letter->data_tambahan ?? [],
            'citizen' => [
                'full_name' => $citizen?->full_name ?? '—',
                'nik' => $citizen?->nik ?? '—',
                'no_kk' => $familyCard?->no_kk ?? '—',
                'place_of_birth' => $citizen?->place_of_birth ?? '—',
                'date_of_birth' => $citizen?->date_of_birth?->translatedFormat('d F Y') ?? '—',
                'gender' => $citizen?->gender ?? '—',
                'religion' => $citizen?->religion ?? '—',
                'occupation' => $citizen?->occupation ?? '—',
                'address' => $familyCard?->address ?? ($citizen?->address ?? 'RT 01 RW 05'),
            ],
            'officer' => [
                'name' => $letter->approvedBy?->name ?? 'Ketua RT 01',
                'role' => 'Ketua RT 01 / RW 05',
            ],
            'issued_at' => ($letter->approved_at ?? now())->translatedFormat('d F Y'),
            'verification_token' => $token,
            'verification_url' => url('/api/v1/public/letter/verify/'.$token),
        ];

        $renderedHtml = view($viewName, $payload)->render();
        $documentHash = hash('sha256', $renderedHtml);

        $safeTicket = Str::slug($letter->ticket_number, '_');
        $fileName = "private/generated-letters/letter_{$safeTicket}_{$token}.html";

        $storageDisk->put($fileName, $renderedHtml);

        $letter->verification_token = $token;
        $letter->file_path = $fileName;
        $letter->document_hash = $documentHash;
        $letter->generated_at = now();
        $letter->save();

        $this->auditService->log(
            action: 'letter.document_generated',
            entityType: 'Letter',
            entityId: $letter->id,
            oldValues: null,
            newValues: [
                'file_path' => $fileName,
                'document_hash' => $documentHash,
                'verification_token' => $token,
            ]
        );

        return $fileName;
    }

    /**
     * Generate printable document for a security report.
     */
    public function generateSecurityReportDocument(SecurityReport $report): string
    {
        $storageDisk = Storage::disk('local');

        $report->loadMissing(['citizen', 'reporter', 'assignee']);

        $payload = [
            'ticket_number' => $report->ticket_number,
            'category' => $report->category,
            'severity' => $report->severity,
            'title' => $report->title,
            'description' => $report->description,
            'location' => $report->location,
            'incident_at' => $report->incident_at?->translatedFormat('d F Y, H:i'),
            'is_anonymous' => $report->is_anonymous,
            'status' => $report->status,
            'resolution' => $report->resolution,
            'resolved_at' => $report->resolved_at?->translatedFormat('d F Y, H:i'),
            'reporter_name' => $report->reporter?->name ?? ($report->citizen?->full_name ?? 'Warga RT 01'),
            'officer_name' => $report->assignee?->name ?? 'Petugas Keamanan RT',
            'ketua_rt_name' => 'Ketua RT 01',
        ];

        $renderedHtml = view('documents.templates.laporan-keamanan', $payload)->render();
        $safeTicket = Str::slug($report->ticket_number, '_');
        $fileName = "private/security-reports/report_{$safeTicket}.html";

        $storageDisk->put($fileName, $renderedHtml);

        return $fileName;
    }
}
