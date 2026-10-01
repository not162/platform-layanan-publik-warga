<?php

namespace App\Services;

use App\Models\Citizen;
use App\Models\FamilyCard;

class CitizenService
{
    public function __construct(protected AuditService $auditService) {}

    public function create(array $data): Citizen
    {
        $data['nik_hash'] = hash('sha256', $data['nik']);
        $citizen = Citizen::create($data);

        $this->auditService->log(
            action: 'create',
            entityType: 'Citizen',
            entityId: $citizen->id,
            oldValues: null,
            newValues: $citizen->toArray()
        );

        return $citizen;
    }

    public function update(Citizen $citizen, array $data): Citizen
    {
        $oldValues = $citizen->toArray();

        if (isset($data['nik'])) {
            $data['nik_hash'] = hash('sha256', $data['nik']);
        }

        if (isset($data['version'])) {
            if ((int) $data['version'] !== (int) $citizen->version) {
                abort(409, 'Conflict: Data warga telah diperbarui oleh pengguna lain. Silakan muat ulang.');
            }
            $data['version'] = (int) $citizen->version + 1;
        }

        $citizen->update($data);

        $this->auditService->log(
            action: 'update',
            entityType: 'Citizen',
            entityId: $citizen->id,
            oldValues: $oldValues,
            newValues: $citizen->toArray()
        );

        return $citizen;
    }

    public function delete(Citizen $citizen): void
    {
        $oldValues = $citizen->toArray();
        $citizenId = $citizen->id;

        Citizen::query()->whereKey($citizenId)->delete();

        $this->auditService->log(
            action: 'delete',
            entityType: 'Citizen',
            entityId: $citizenId,
            oldValues: $oldValues,
            newValues: null
        );
    }

    public function findByNik(string $nik): ?Citizen
    {
        $nikHash = hash('sha256', $nik);

        return Citizen::query()->where('nik_hash', $nikHash)->first();
    }

    public function findByNoKk(string $noKk): ?FamilyCard
    {
        $kkHash = hash('sha256', $noKk);

        return FamilyCard::query()->where('no_kk_hash', $kkHash)->first();
    }
}
