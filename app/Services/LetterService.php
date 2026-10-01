<?php

namespace App\Services;

use App\Models\Letter;

class LetterService
{
    /**
     * Create a new class instance.
     */
    public function __construct(protected AuditService $auditService) {}

    public function create(array $data): Letter
    {
        $letter = Letter::create($data);

        $this->auditService->log('create', 'Letter', $letter->id, null, $letter->toArray());

        return $letter;
    }

    public function updateStatus(Letter $letter, string $status, array $additionalData = []): Letter
    {
        $oldValues = $letter->toArray();

        // Enforce Optimistic Locking if provided in additionalData
        if (isset($additionalData['version'])) {
            if ((int) $additionalData['version'] !== (int) $letter->version) {
                abort(409, 'Conflict: Letter data has been updated by another user. Please refresh and try again.');
            }
            $additionalData['version'] = (int) $letter->version + 1;
        }

        $letter->status = $status;

        if (isset($additionalData['version'])) {
            $letter->version = $additionalData['version'];
        }

        // e.g. ticket_number, letter_number
        if (isset($additionalData['ticket_number'])) {
            $letter->ticket_number = $additionalData['ticket_number'];
        }
        if (isset($additionalData['letter_number'])) {
            $letter->letter_number = $additionalData['letter_number'];
        }

        $letter->save();

        $this->auditService->log('update_status', 'Letter', $letter->id, $oldValues, $letter->toArray());

        return $letter;
    }
}
