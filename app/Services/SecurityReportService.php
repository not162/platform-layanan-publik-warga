<?php

namespace App\Services;

use App\Models\SecurityReport;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SecurityReportService
{
    public function __construct(
        protected AuditService $auditService,
        protected DocumentGeneratorService $documentGeneratorService
    ) {}

    /**
     * Create a new security report.
     */
    public function create(array $data, ?User $reporter = null): SecurityReport
    {
        return DB::transaction(function () use ($data, $reporter) {
            $data['ticket_number'] = $this->generateTicketNumber();
            $data['status'] = 'submitted';
            $data['version'] = 1;

            if ($reporter) {
                $data['reporter_user_id'] = $reporter->id;
                $data['citizen_id'] = $data['citizen_id'] ?? ($reporter->citizen?->id ?? null);
            }

            $report = SecurityReport::create($data);

            $this->auditService->log(
                action: 'security.report.created',
                entityType: 'SecurityReport',
                entityId: $report->id,
                oldValues: null,
                newValues: $report->toArray()
            );

            return $report;
        });
    }

    /**
     * Assign security report to a security officer.
     */
    public function assign(SecurityReport $report, User $officer, int $version, int $assigneeId): SecurityReport
    {
        return DB::transaction(function () use ($report, $version, $assigneeId) {
            /** @var SecurityReport $locked */
            $locked = SecurityReport::query()->lockForUpdate()->findOrFail($report->id);

            if ((int) $version !== (int) $locked->version) {
                abort(409, 'Konflik data: Laporan keamanan telah diperbarui pengguna lain.');
            }

            $oldValues = $locked->toArray();
            $locked->assigned_to = $assigneeId;
            $locked->status = 'assigned';
            $locked->version = $locked->version + 1;
            $locked->save();

            $this->auditService->log('security.report.assigned', 'SecurityReport', $locked->id, $oldValues, $locked->toArray());

            return $locked;
        });
    }

    /**
     * Update status and resolution for a security report.
     */
    public function updateStatus(SecurityReport $report, User $officer, int $version, string $newStatus, ?string $resolution = null): SecurityReport
    {
        return DB::transaction(function () use ($report, $version, $newStatus, $resolution) {
            /** @var SecurityReport $locked */
            $locked = SecurityReport::query()->lockForUpdate()->findOrFail($report->id);

            if ((int) $version !== (int) $locked->version) {
                abort(409, 'Konflik data: Laporan keamanan telah diperbarui pengguna lain.');
            }

            $allowedStatuses = ['submitted', 'reviewed', 'assigned', 'processing', 'resolved', 'closed', 'rejected'];
            if (! in_array($newStatus, $allowedStatuses, true)) {
                abort(422, "Status '{$newStatus}' tidak valid.");
            }

            $oldValues = $locked->toArray();
            $locked->status = $newStatus;

            if ($resolution !== null) {
                $locked->resolution = $resolution;
            }

            if ($newStatus === 'resolved') {
                $locked->resolved_at = now();
            }

            $locked->version = $locked->version + 1;
            $locked->save();

            $this->auditService->log('security.report.updated', 'SecurityReport', $locked->id, $oldValues, $locked->toArray());

            return $locked;
        });
    }

    public function generateTicketNumber(): string
    {
        $prefix = 'SEC';
        $date = now()->format('Ymd');
        $random = strtoupper(Str::random(5));

        return "{$prefix}-{$date}-{$random}";
    }
}
