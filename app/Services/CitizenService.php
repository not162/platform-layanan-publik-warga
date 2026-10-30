<?php

namespace App\Services;

use App\Http\Middleware\EnsureUserIsActive;
use App\Models\Citizen;
use App\Models\FamilyCard;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CitizenService
{
    public function __construct(
        protected AuditService $auditService,
        protected NotifyService $notifyService,
    ) {}

    /**
     * Verifikasi pendaftaran warga: status 'tetap', data & akun diaktifkan, pemohon diberi notifikasi.
     * Satu-satunya jalur verifikasi (dipakai /manage/citizens dan /admin/citizens).
     */
    public function verify(Citizen $citizen, User $actor, ?string $notes = null): Citizen
    {
        abort_unless($actor->hasPermission('citizen.manage'), 403, 'Akses verifikasi warga ditolak.');

        $notes = $notes !== null ? trim($notes) : null;
        if ($notes !== null && mb_strlen($notes) > 500) {
            throw ValidationException::withMessages(['notes' => ['Catatan verifikasi maksimal 500 karakter.']]);
        }

        $verified = DB::transaction(function () use ($citizen, $actor, $notes) {
            /** @var Citizen $locked */
            $locked = Citizen::query()->lockForUpdate()->findOrFail($citizen->id);

            if ($locked->status_warga !== 'pending_verification') {
                abort(409, 'Hanya pendaftaran berstatus menunggu verifikasi yang dapat diverifikasi.');
            }

            $oldValues = $locked->toArray();
            $locked->update([
                'status_warga' => 'tetap',
                'is_active' => true,
                'verification_notes' => 'Telah diverifikasi oleh '.$actor->name.($notes ? ': '.$notes : '.'),
                'version' => (int) $locked->version + 1,
            ]);
            $locked->user?->update(['is_active' => true]);

            $this->auditService->log('citizen.verified', 'Citizen', $locked->id, $oldValues, $locked->toArray());

            return $locked;
        });

        $this->notifyService->to($verified->user, 'Data kependudukan terverifikasi', 'Pengurus RT telah memverifikasi data Anda. Semua layanan kini tersedia.', '/dashboard', 'success');

        return $verified;
    }

    /**
     * Tolak pendaftaran warga: status 'ditolak', data & akun dinonaktifkan, seluruh token/sesi dicabut.
     */
    public function reject(Citizen $citizen, User $actor, string $reason): Citizen
    {
        abort_unless($actor->hasPermission('citizen.manage'), 403, 'Akses penolakan warga ditolak.');

        $reason = trim($reason);
        if ($reason === '' || mb_strlen($reason) > 500) {
            throw ValidationException::withMessages(['reason' => ['Alasan penolakan wajib diisi (maksimal 500 karakter).']]);
        }

        $rejected = DB::transaction(function () use ($citizen, $reason) {
            /** @var Citizen $locked */
            $locked = Citizen::query()->lockForUpdate()->findOrFail($citizen->id);

            if ($locked->status_warga !== 'pending_verification') {
                abort(409, 'Hanya pendaftaran berstatus menunggu verifikasi yang dapat ditolak.');
            }

            $oldValues = $locked->toArray();
            $locked->update([
                'status_warga' => 'ditolak',
                'is_active' => false,
                'verification_notes' => 'Ditolak: '.$reason,
                'version' => (int) $locked->version + 1,
            ]);

            if ($locked->user) {
                $locked->user->update(['is_active' => false]);
                EnsureUserIsActive::revokeAccess($locked->user);
            }

            $this->auditService->log('citizen.rejected', 'Citizen', $locked->id, $oldValues, $locked->toArray());

            return $locked;
        });

        $this->notifyService->to($rejected->user, 'Pendaftaran ditolak', $reason, '/login', 'danger');

        return $rejected;
    }

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
        if ($citizen->letters()->exists() || $citizen->dues()->exists() || $citizen->complaints()->exists()) {
            abort(422, 'Tidak dapat menghapus data warga yang telah memiliki riwayat persuratan, iuran, atau pengaduan. Nonaktifkan status warga.');
        }

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
