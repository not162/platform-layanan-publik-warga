<?php

namespace App\Services;

use App\Models\Complaint;

class ComplaintService
{
    /**
     * Create a new class instance.
     */
    public function __construct(protected AuditService $auditService) {}

    public function create(array $data): Complaint
    {
        $complaint = Complaint::create($data);

        $this->auditService->log('create', 'Complaint', $complaint->id, null, $complaint->toArray());

        return $complaint;
    }

    public function updateStatus(Complaint $complaint, string $status, array $additionalData = []): Complaint
    {
        $oldValues = $complaint->toArray();

        if (isset($additionalData['version'])) {
            if ((int) $additionalData['version'] !== (int) $complaint->version) {
                abort(409, 'Conflict: Complaint data has been updated by another user. Please refresh and try again.');
            }
            $complaint->version = (int) $complaint->version + 1;
        }

        $complaint->status = $status;
        $complaint->save();

        $this->auditService->log('update_status', 'Complaint', $complaint->id, $oldValues, $complaint->toArray());

        return $complaint;
    }
}
