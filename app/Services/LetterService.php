<?php

namespace App\Services;

use App\Models\Letter;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LetterService
{
    public function __construct(protected AuditService $auditService) {}

    /**
     * Create a new letter request (draft or directly submitted).
     */
    public function create(array $data, ?User $user = null): Letter
    {
        return DB::transaction(function () use ($data) {
            $data['ticket_number'] = $data['ticket_number'] ?? $this->generateTicketNumber();
            $data['status'] = $data['status'] ?? 'draft';
            $data['version'] = 1;

            $letter = Letter::create($data);

            $this->auditService->log(
                action: 'letter.submitted',
                entityType: 'Letter',
                entityId: $letter->id,
                oldValues: null,
                newValues: $letter->toArray()
            );

            return $letter;
        });
    }

    /**
     * Transition: draft -> submitted
     */
    public function submit(Letter $letter, User $user, int $version): Letter
    {
        return DB::transaction(function () use ($letter, $version) {
            /** @var Letter $locked */
            $locked = Letter::query()->lockForUpdate()->findOrFail($letter->id);

            if ((int) $version !== (int) $locked->version) {
                abort(409, 'Konflik data: Versi data surat telah berubah.');
            }

            if ($locked->status !== 'draft') {
                abort(409, "Transisi tidak sah: Hanya surat berstatus draft yang dapat diajukan (status saat ini: {$locked->status}).");
            }

            $oldValues = $locked->toArray();
            $locked->status = 'submitted';
            $locked->version = $locked->version + 1;
            $locked->save();

            $this->auditService->log('letter.submitted', 'Letter', $locked->id, $oldValues, $locked->toArray());

            return $locked;
        });
    }

    /**
     * Transition: submitted -> verified
     */
    public function verify(Letter $letter, User $user, int $version, ?string $notes = null): Letter
    {
        if (! $user->hasPermission('letter.verify')) {
            abort(403, 'Akses ditolak: Membutuhkan izin verifikasi surat (letter.verify).');
        }

        return DB::transaction(function () use ($letter, $user, $version, $notes) {
            /** @var Letter $locked */
            $locked = Letter::query()->lockForUpdate()->findOrFail($letter->id);

            if ((int) $version !== (int) $locked->version) {
                abort(409, 'Konflik data: Versi surat telah diperbarui pengguna lain.');
            }

            if ($locked->status !== 'submitted') {
                abort(409, "Transisi tidak sah: Hanya surat berstatus submitted yang dapat diverifikasi (status saat ini: {$locked->status}).");
            }

            $oldValues = $locked->toArray();
            $locked->status = 'verified';
            $locked->verified_by = $user->id;
            $locked->verified_at = now();
            if ($notes) {
                $locked->catatan_admin = $notes;
            }
            $locked->version = $locked->version + 1;
            $locked->save();

            $this->auditService->log('letter.verified', 'Letter', $locked->id, $oldValues, $locked->toArray());

            return $locked;
        });
    }

    /**
     * Transition: verified -> approved
     */
    public function approve(Letter $letter, User $user, int $version, ?string $customLetterNumber = null): Letter
    {
        if (! $user->hasPermission('letter.approve')) {
            abort(403, 'Akses ditolak: Membutuhkan izin persetujuan surat (letter.approve).');
        }

        return DB::transaction(function () use ($letter, $user, $version, $customLetterNumber) {
            /** @var Letter $locked */
            $locked = Letter::query()->lockForUpdate()->findOrFail($letter->id);

            if ((int) $version !== (int) $locked->version) {
                abort(409, 'Konflik data: Versi surat telah diperbarui pengguna lain.');
            }

            if ($locked->status !== 'verified') {
                abort(409, "Transisi tidak sah: Hanya surat berstatus verified yang dapat disetujui (status saat ini: {$locked->status}).");
            }

            $oldValues = $locked->toArray();
            $locked->status = 'approved';
            $locked->approved_by = $user->id;
            $locked->approved_at = now();
            $locked->letter_number = $customLetterNumber ?? $this->generateOfficialNumber($locked);
            $locked->verification_token = Str::random(40);
            $locked->version = $locked->version + 1;
            $locked->save();

            $this->auditService->log('letter.approved', 'Letter', $locked->id, $oldValues, $locked->toArray());

            return $locked;
        });
    }

    /**
     * Transition: submitted | verified -> rejected
     */
    public function reject(Letter $letter, User $user, int $version, string $reason): Letter
    {
        if (! $user->hasPermission('letter.verify') && ! $user->hasPermission('letter.approve') && ! $user->isSuperadmin()) {
            abort(403, 'Akses ditolak: Tidak memiliki wewenang penolakan surat.');
        }

        if (trim($reason) === '') {
            abort(422, 'Alasan penolakan (rejection_reason) wajib diisi.');
        }

        return DB::transaction(function () use ($letter, $version, $reason) {
            /** @var Letter $locked */
            $locked = Letter::query()->lockForUpdate()->findOrFail($letter->id);

            if ((int) $version !== (int) $locked->version) {
                abort(409, 'Konflik data: Versi surat telah diperbarui.');
            }

            if (! in_array($locked->status, ['submitted', 'verified'])) {
                abort(409, "Transisi tidak sah: Surat berstatus '{$locked->status}' tidak dapat ditolak.");
            }

            $oldValues = $locked->toArray();
            $locked->status = 'rejected';
            $locked->rejection_reason = $reason;
            $locked->version = $locked->version + 1;
            $locked->save();

            $this->auditService->log('letter.rejected', 'Letter', $locked->id, $oldValues, $locked->toArray());

            return $locked;
        });
    }

    /**
     * Generic status updater for backward-compatible controller routing.
     */
    public function updateStatus(Letter $letter, string $newStatus, array $data = []): Letter
    {
        /** @var User $user */
        $user = Auth::user() ?? User::query()->first();
        $version = (int) ($data['version'] ?? $letter->version);

        return match ($newStatus) {
            'submitted' => $this->submit($letter, $user, $version),
            'verified' => $this->verify($letter, $user, $version, $data['catatan_admin'] ?? null),
            'approved' => $this->approve($letter, $user, $version, $data['letter_number'] ?? null),
            'rejected' => $this->reject($letter, $user, $version, $data['rejection_reason'] ?? 'Persyaratan tidak memenuhi kriteria'),
            default => abort(409, "Transisi status '{$newStatus}' tidak didukung."),
        };
    }

    public function generateTicketNumber(): string
    {
        $prefix = 'SRT';
        $date = now()->format('Ymd');
        $random = strtoupper(Str::random(5));

        return "{$prefix}-{$date}-{$random}";
    }

    protected function generateOfficialNumber(Letter $letter): string
    {
        $year = now()->year;
        $month = now()->format('m');
        $sequence = Letter::query()->whereNotNull('letter_number')->get()->count() + 1;
        $padded = str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);

        return "SK/{$padded}/RT01/{$month}/{$year}";
    }

    public function findByToken(string $token): ?Letter
    {
        return Letter::query()
            ->with(['citizen', 'letterType'])
            ->where('verification_token', $token)
            ->whereIn('status', ['approved', 'completed'])
            ->first();
    }

    public function findByTicket(string $ticketNumber): ?Letter
    {
        return Letter::query()
            ->with('letterType')
            ->where('ticket_number', $ticketNumber)
            ->first();
    }
}
