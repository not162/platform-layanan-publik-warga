<?php

namespace App\Services;

use App\Models\Complaint;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ComplaintService
{
    public function __construct(protected AuditService $auditService) {}

    public function create(array $data, ?User $user = null): Complaint
    {
        return DB::transaction(function () use ($data, $user) {
            $data['ticket_number'] = $data['ticket_number'] ?? $this->generateTicketNumber();
            $data['status'] = $data['status'] ?? 'submitted';
            $data['priority'] = $data['priority'] ?? 'sedang';
            $data['version'] = 1;

            $data['is_anonymous'] = ! empty($data['is_anonymous']);
            if ($user) {
                $data['user_id'] = $user->id;
                $data['citizen_id'] = $user->citizen?->id;
            }

            $complaint = Complaint::create($data);

            $this->auditService->log(
                action: 'complaint.submitted',
                entityType: 'Complaint',
                entityId: $complaint->id,
                oldValues: null,
                newValues: $complaint->toArray()
            );

            return $complaint;
        });
    }

    public function updateStatus(Complaint $complaint, string $status, array $data = [], ?User $admin = null): Complaint
    {
        $allowedStatuses = ['submitted', 'reviewed', 'processing', 'resolved', 'closed', 'rejected'];
        if (! in_array($status, $allowedStatuses, true)) {
            abort(422, "Status pengaduan '{$status}' tidak valid.");
        }

        return DB::transaction(function () use ($complaint, $status, $data, $admin) {
            /** @var Complaint $locked */
            $locked = Complaint::query()->lockForUpdate()->findOrFail($complaint->id);

            if (isset($data['version']) && (int) $data['version'] !== (int) $locked->version) {
                abort(409, 'Konflik data: Pengaduan telah diperbarui oleh pengguna lain.');
            }

            $oldValues = $locked->toArray();

            $locked->status = $status;
            $locked->version = $locked->version + 1;

            if (isset($data['admin_response'])) {
                $locked->admin_response = $data['admin_response'];
            }

            if (isset($data['priority'])) {
                $locked->priority = $data['priority'];
            }

            if (isset($data['assigned_admin_id'])) {
                $locked->assigned_admin_id = $data['assigned_admin_id'];
            } elseif ($admin && ! $locked->assigned_admin_id) {
                $locked->assigned_admin_id = $admin->id;
            }

            $locked->save();

            $this->auditService->log(
                action: 'complaint.status.changed',
                entityType: 'Complaint',
                entityId: $locked->id,
                oldValues: $oldValues,
                newValues: $locked->toArray()
            );

            return $locked;
        });
    }

    public function generateTicketNumber(): string
    {
        $prefix = 'ADU';
        $date = now()->format('Ymd');
        $random = strtoupper(Str::random(5));

        return "{$prefix}-{$date}-{$random}";
    }

    public function findByTicket(string $ticketNumber): ?Complaint
    {
        return Complaint::query()
            ->where('ticket_number', $ticketNumber)
            ->first();
    }
}
