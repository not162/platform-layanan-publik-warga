<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Events\LetterStatusUpdated;
use App\Models\DocumentSequence;
use App\Models\Letter;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LetterService
{
    public function __construct(
        protected AuditService $auditService,
        protected DocumentGeneratorService $documentGeneratorService,
        protected NotifyService $notifyService
    ) {}

    /**
     * Penolakan mengikuti tahap alur: surat 'submitted' ditolak pemverifikasi (Sekretaris, letter.verify),
     * surat 'verified' ditolak pengesah (Ketua RT, letter.approve). Superadmin sistem tetap bypass.
     */
    public static function canReject(User $user, Letter $letter): bool
    {
        if ($user->isSuperadmin()) {
            return in_array($letter->status, ['submitted', 'verified'], true);
        }

        return match ($letter->status) {
            'submitted' => $user->hasPermission('letter.verify'),
            'verified' => $user->hasPermission('letter.approve'),
            default => false,
        };
    }

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

            LetterStatusUpdated::dispatch($locked, 'submitted');

            return $locked;
        });
    }

    /**
     * Transition: submitted -> verified
     */
    public function verify(Letter $letter, User $user, int $version, ?string $notes = null): Letter
    {
        if (! $user->hasPermission('letter.verify') && ! $user->isSuperadmin()) {
            abort(403, 'Akses ditolak: Membutuhkan izin verifikasi surat (letter.verify).');
        }

        if ($notes !== null && mb_strlen($notes) > 500) {
            throw ValidationException::withMessages(['notes' => ['Catatan verifikasi maksimal 500 karakter.']]);
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

            LetterStatusUpdated::dispatch($locked, 'verified');

            return $locked;
        });
    }

    /**
     * Transition: verified -> approved
     */
    public function approve(Letter $letter, User $user, int $version, ?string $customLetterNumber = null): Letter
    {
        if (! $user->hasPermission('letter.approve') && ! $user->isSuperadmin()) {
            abort(403, 'Akses ditolak: Membutuhkan izin persetujuan surat (letter.approve).');
        }

        $approved = DB::transaction(function () use ($letter, $user, $version, $customLetterNumber) {
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

            // Automatically generate official printable document
            $this->documentGeneratorService->generateLetterDocument($locked);

            $this->auditService->log('letter.approved', 'Letter', $locked->id, $oldValues, $locked->toArray());

            LetterStatusUpdated::dispatch($locked, 'approved');

            return $locked;
        });

        // Sekretaris (letter.complete) menyerahkan surat lalu menandainya selesai.
        $this->notifyService->toRoles(
            [UserRole::SEKRETARIS],
            'Surat siap diselesaikan',
            "{$approved->ticket_number} telah disahkan Ketua RT (No. {$approved->letter_number}). Serahkan ke pemohon lalu tandai selesai.",
            '/app/letters',
            'info',
        );

        return $approved;
    }

    /**
     * Transition: approved -> completed
     */
    public function complete(Letter $letter, User $user, int $version): Letter
    {
        if (! $user->hasPermission('letter.complete') && ! $user->hasPermission('letter.approve') && ! $user->isSuperadmin()) {
            abort(403, 'Akses ditolak: Membutuhkan izin penyelesaian surat.');
        }

        return DB::transaction(function () use ($letter, $version) {
            /** @var Letter $locked */
            $locked = Letter::query()->lockForUpdate()->findOrFail($letter->id);

            if ((int) $version !== (int) $locked->version) {
                abort(409, 'Konflik data: Versi surat telah diperbarui pengguna lain.');
            }

            if ($locked->status !== 'approved') {
                abort(409, "Transisi tidak sah: Hanya surat berstatus approved yang dapat diselesaikan (status saat ini: {$locked->status}).");
            }

            $oldValues = $locked->toArray();
            $locked->status = 'completed';
            $locked->version = $locked->version + 1;

            // Ensure document is generated
            $this->documentGeneratorService->generateLetterDocument($locked);
            $locked->save();

            $this->auditService->log('letter.completed', 'Letter', $locked->id, $oldValues, $locked->toArray());

            LetterStatusUpdated::dispatch($locked, 'completed');

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

        return DB::transaction(function () use ($letter, $user, $version, $reason) {
            /** @var Letter $locked */
            $locked = Letter::query()->lockForUpdate()->findOrFail($letter->id);

            if ((int) $version !== (int) $locked->version) {
                abort(409, 'Konflik data: Versi surat telah diperbarui.');
            }

            if (! in_array($locked->status, ['submitted', 'verified'], true)) {
                abort(409, "Transisi tidak sah: Surat berstatus '{$locked->status}' tidak dapat ditolak.");
            }

            if (! self::canReject($user, $locked)) {
                abort(403, $locked->status === 'submitted'
                    ? 'Akses ditolak: Surat yang belum diverifikasi hanya dapat ditolak oleh Sekretaris RT (letter.verify).'
                    : 'Akses ditolak: Surat terverifikasi hanya dapat ditolak oleh Ketua RT (letter.approve).');
            }

            $oldValues = $locked->toArray();
            $locked->status = 'rejected';
            $locked->rejection_reason = $reason;
            $locked->version = $locked->version + 1;
            $locked->save();

            $this->auditService->log('letter.rejected', 'Letter', $locked->id, $oldValues, $locked->toArray());

            LetterStatusUpdated::dispatch($locked, 'rejected');

            return $locked;
        });
    }

    /**
     * Generic status updater for backward-compatible controller routing.
     *
     * @deprecated Use dedicated action endpoints: submit(), verify(), approve(), complete(), reject()
     */
    public function updateStatus(Letter $letter, string $newStatus, array $data = [], ?User $user = null): Letter
    {
        $actor = $user ?? Auth::user();

        if (! $actor) {
            abort(401, 'Unauthenticated: Aktor pengguna harus terotentikasi.');
        }

        $version = (int) ($data['version'] ?? $letter->version);

        if ($letter->status === 'completed' && $newStatus !== 'completed') {
            abort(409, 'Surat yang telah berstatus selesai (completed) bersifat final dan tidak dapat diubah statusnya.');
        }

        if ($newStatus === 'draft') {
            abort(409, 'Surat tidak dapat dikembalikan menjadi status draf.');
        }

        return match ($newStatus) {
            'submitted' => $this->submit($letter, $actor, $version),
            'verified' => $this->verify($letter, $actor, $version, $data['catatan_admin'] ?? null),
            'approved' => $this->approve($letter, $actor, $version, $data['letter_number'] ?? null),
            'completed' => $this->complete($letter, $actor, $version),
            'rejected' => $this->reject($letter, $actor, $version, $data['rejection_reason'] ?? 'Persyaratan tidak memenuhi kriteria'),
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

    /**
     * Concurrency-safe document numbering using transactional sequence lock.
     */
    protected function generateOfficialNumber(Letter $letter): string
    {
        $letter->loadMissing('letterType');
        $docType = $letter->letterType?->kode_surat ?? 'SK';
        $year = (int) now()->year;
        $month = (int) now()->month;

        /** @var DocumentSequence $sequence */
        $sequence = DocumentSequence::query()
            ->lockForUpdate()
            ->firstOrCreate(
                ['document_type' => $docType, 'year' => $year, 'month' => $month],
                ['last_number' => 0]
            );

        $sequence->last_number += 1;
        $sequence->save();

        $padded = str_pad((string) $sequence->last_number, 4, '0', STR_PAD_LEFT);
        $monthStr = str_pad((string) $month, 2, '0', STR_PAD_LEFT);

        $format = $letter->letterType?->format_penomoran ?? 'SK/{nomor}/RT04/{bulan}/{tahun}';

        return str_replace(
            ['{nomor}', '{bulan}', '{tahun}'],
            [$padded, $monthStr, (string) $year],
            $format
        );
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
