<?php

namespace App\Services;

use App\Models\Announcement;

class AnnouncementService
{
    /**
     * Create a new class instance.
     */
    public function __construct(protected AuditService $auditService) {}

    public function create(array $data): Announcement
    {
        $announcement = Announcement::create($data);

        $this->auditService->log('create', 'Announcement', $announcement->id, null, $announcement->toArray());

        return $announcement;
    }

    public function update(Announcement $announcement, array $data): Announcement
    {
        $oldValues = $announcement->toArray();

        if (isset($data['version'])) {
            if ((int) $data['version'] !== (int) $announcement->version) {
                abort(409, 'Conflict: Announcement has been modified.');
            }
            $data['version'] = (int) $announcement->version + 1;
        }

        if (isset($data['is_published']) && $data['is_published'] && ! $announcement->is_published) {
            $data['published_at'] = now();
        }

        $announcement->update($data);

        $this->auditService->log('update', 'Announcement', $announcement->id, $oldValues, $announcement->toArray());

        return $announcement;
    }

    public function delete(Announcement $announcement): bool
    {
        $id = $announcement->id;
        $oldValues = $announcement->toArray();
        $deleted = (bool) Announcement::query()->whereKey($id)->delete();

        $this->auditService->log('delete', 'Announcement', $id, $oldValues, null);

        return $deleted;
    }
}
