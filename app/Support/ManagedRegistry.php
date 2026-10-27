<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\Citizen;
use App\Models\CommunityEvent;
use App\Models\Complaint;
use App\Models\EmergencyContact;
use App\Models\FamilyCard;
use App\Models\FinanceTransaction;
use App\Models\Letter;
use App\Models\LetterType;
use App\Models\ResidentDue;
use App\Models\RoundSchedule;
use App\Models\RtOfficer;
use App\Models\SecurityReport;
use App\Models\User;
use App\Services\ComplaintService;
use App\Services\FinanceService;
use App\Services\LetterService;
use App\Services\NotifyService;
use App\Services\ResidentDueService;
use App\Services\SecurityReportService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Definisi seluruh sumber data CRUD yang tersedia di /api/v1/manage/{resource}.
 *
 * Setiap definisi memuat: model, hak akses (read/create/update/delete), scope query,
 * transformasi JSON, aturan validasi, serta aksi khusus (mis. verifikasi surat).
 */
class ManagedRegistry
{
    /**
     * @return array<string, mixed>|null
     */
    public static function get(string $key): ?array
    {
        $all = self::all();

        return $all[$key] ?? null;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            'users' => self::users(),
            'citizens' => self::citizens(),
            'announcements' => self::announcements(),
            'events' => self::events(),
            'officers' => self::officers(),
            'schedules' => self::schedules(),
            'contacts' => self::contacts(),
            'letters' => self::letters(),
            'complaints' => self::complaints(),
            'security-reports' => self::securityReports(),
            'finance' => self::finance(),
            'dues' => self::dues(),
            'audit-logs' => self::auditLogs(),
        ];
    }

    public static function iso(mixed $value): ?string
    {
        return $value?->toIso8601String();
    }

    /**
     * ORDER BY berurutan khusus yang portabel (MySQL & SQLite).
     *
     * @param  array<int, string>  $order
     */
    protected static function orderField(string $column, array $order): string
    {
        $cases = collect($order)->map(fn (string $value, int $i) => "WHEN '{$value}' THEN {$i}")->implode(' ');

        return "CASE {$column} {$cases} ELSE 99 END";
    }

    protected static function notify(): NotifyService
    {
        return app(NotifyService::class);
    }

    protected static function mask(?string $number): ?string
    {
        if (! $number) {
            return null;
        }

        return strlen($number) >= 8 ? substr($number, 0, 4).str_repeat('*', strlen($number) - 8).substr($number, -4) : '****';
    }

    protected static function ownCitizenId(User $user): ?int
    {
        return $user->citizen?->id;
    }

    /* ------------------------------------------------------------------ */
    /* Pengguna (superadmin & admin) */
    /* ------------------------------------------------------------------ */
    protected static function users(): array
    {
        $manageable = fn (User $actor, User $target): bool => $actor->isSuperadmin()
            || in_array($target->role, $actor->role->assignableRoles(), true);

        return [
            'model' => User::class,
            'search' => ['name', 'username', 'email'],
            'abilities' => ['read' => 'user.manage', 'create' => 'user.manage', 'update' => 'user.manage', 'delete' => 'user.manage'],
            'query' => fn (Builder $q) => $q->orderByRaw(self::orderField('role', ['SUPERADMIN', 'ADMIN', 'KETUA_RT', 'SEKRETARIS', 'BENDAHARA', 'PETUGAS_KEAMANAN', 'WARGA']))->orderBy('name'),
            'filters' => ['role', 'is_active'],
            'transform' => fn (User $u, User $actor) => [
                'id' => $u->id,
                'name' => $u->name,
                'username' => $u->username,
                'email' => $u->email,
                'role' => $u->role->value,
                'role_label' => $u->role->label(),
                'tier' => $u->tier(),
                'is_active' => $u->is_active,
                'last_login_at' => self::iso($u->last_login_at),
                'created_at' => self::iso($u->created_at),
                'updated_at' => self::iso($u->updated_at),
                'can_edit' => $manageable($actor, $u),
                'can_delete' => $manageable($actor, $u) && $u->id !== $actor->id,
            ],
            'rules' => function (Request $r, User $actor, ?Model $m) {
                $roles = array_map(fn (UserRole $role) => $role->value, $actor->role->assignableRoles());

                return [
                    'name' => ['required', 'string', 'max:150'],
                    'username' => ['required', 'regex:/^[a-z0-9_.]{4,30}$/', Rule::unique('users', 'username')->ignore($m?->id)],
                    'email' => ['nullable', 'email', 'max:190', Rule::unique('users', 'email')->ignore($m?->id)],
                    'role' => ['required', Rule::in($roles)],
                    'is_active' => ['nullable', 'boolean'],
                    'password' => [$m ? 'nullable' : 'required', 'string', 'min:8'],
                ];
            },
            'prepare' => function (array $data, User $actor, ?Model $m) use ($manageable) {
                if ($m && ! $manageable($actor, $m)) {
                    abort(403, 'Anda tidak berwenang mengubah akun dengan peran ini.');
                }
                if ($m && $m->id === $actor->id && (isset($data['role']) && $data['role'] !== $m->role->value || array_key_exists('is_active', $data) && ! $data['is_active'])) {
                    abort(422, 'Anda tidak dapat mengubah peran atau menonaktifkan akun sendiri.');
                }
                $data['username'] = strtolower($data['username']);
                if (empty($data['password'])) {
                    unset($data['password']);
                }

                return $data;
            },
            'after_store' => fn (Model $m, User $actor) => self::notify()->to($m, 'Akun dibuat', "Akun Anda dibuat oleh {$actor->name} dengan peran {$m->role->label()}.", '/dashboard', 'success'),
            'before_delete' => function (Model $m, User $actor) use ($manageable) {
                if (! $manageable($actor, $m) || $m->id === $actor->id) {
                    abort(403, 'Akun ini tidak dapat dihapus.');
                }
            },
        ];
    }

    /* ------------------------------------------------------------------ */
    /* Data warga */
    /* ------------------------------------------------------------------ */
    protected static function citizens(): array
    {
        return [
            'model' => Citizen::class,
            'search' => ['full_name', 'phone'],
            'abilities' => ['read' => 'citizen.read', 'create' => 'citizen.manage', 'update' => 'citizen.manage', 'delete' => 'citizen.manage'],
            'with' => ['familyCard', 'user'],
            'query' => fn (Builder $q) => $q->orderByRaw("status_warga = 'pending_verification' DESC")->latest('updated_at'),
            'filters' => ['status_warga'],
            'transform' => fn (Citizen $c, User $actor) => [
                'id' => $c->id,
                'full_name' => $c->full_name,
                'nik_masked' => self::mask($c->nik),
                'no_kk_masked' => self::mask($c->familyCard?->no_kk),
                'gender' => $c->gender,
                'place_of_birth' => $c->place_of_birth,
                'date_of_birth' => $c->date_of_birth?->toDateString(),
                'occupation' => $c->occupation,
                'phone' => $c->phone,
                'status_warga' => $c->status_warga,
                'is_active' => $c->is_active,
                'username' => $c->user?->username,
                'has_account' => $c->user_id !== null,
                'ktp_url' => $c->ktp_file_path && $actor->hasPermission('citizen.manage') ? url('/api/v1/files/ktp/'.$c->id) : null,
                'version' => $c->version,
                'created_at' => self::iso($c->created_at),
                'updated_at' => self::iso($c->updated_at),
                'can_edit' => $actor->hasPermission('citizen.manage'),
                'can_delete' => $actor->hasPermission('citizen.manage') && $c->user_id === null,
                'can_verify' => $actor->hasPermission('citizen.manage') && $c->status_warga === 'pending_verification',
            ],
            'rules' => function (Request $r, User $actor, ?Model $m) {
                $rules = [
                    'full_name' => ['required', 'string', 'min:3', 'max:150'],
                    'gender' => ['nullable', Rule::in(['Laki-laki', 'Perempuan'])],
                    'place_of_birth' => ['nullable', 'string', 'max:100'],
                    'date_of_birth' => ['nullable', 'date'],
                    'occupation' => ['nullable', 'string', 'max:120'],
                    'phone' => ['nullable', 'string', 'max:25'],
                    'status_warga' => ['nullable', Rule::in(['tetap', 'pending_verification', 'pindah'])],
                    'is_active' => ['nullable', 'boolean'],
                ];

                if (! $m) {
                    $rules['nik'] = ['required', 'digits:16'];
                    $rules['no_kk'] = ['required', 'digits:16'];
                }

                return $rules;
            },
            'store' => function (array $data, User $actor) {
                $nikHash = hash('sha256', $data['nik']);
                if (Citizen::query()->where('nik_hash', $nikHash)->exists()) {
                    throw ValidationException::withMessages(['nik' => ['NIK sudah terdaftar.']]);
                }
                $card = FamilyCard::query()->where('no_kk_hash', hash('sha256', $data['no_kk']))->first()
                    ?? FamilyCard::create([
                        'no_kk' => $data['no_kk'],
                        'no_kk_hash' => hash('sha256', $data['no_kk']),
                        'address' => 'Gg. Bugis, RT 04 / RW 09, Kemayoran, Jakarta Pusat',
                        'rt' => '004',
                        'rw' => '009',
                        'version' => 1,
                    ]);

                if (Citizen::query()->where('family_card_id', $card->id)->where('full_name_key', Citizen::nameKey($data['full_name']))->exists()) {
                    throw ValidationException::withMessages(['full_name' => ['Nama dan No. KK ini sudah terdaftar.']]);
                }

                unset($data['no_kk']);

                return Citizen::create($data + ['nik_hash' => $nikHash, 'family_card_id' => $card->id, 'status_warga' => $data['status_warga'] ?? 'tetap', 'version' => 1]);
            },
            'prepare' => function (array $data, User $actor, ?Model $m) {
                if ($m && isset($data['full_name']) && $m->family_card_id) {
                    $dup = Citizen::query()
                        ->where('family_card_id', $m->family_card_id)
                        ->where('full_name_key', Citizen::nameKey($data['full_name']))
                        ->where('id', '!=', $m->id)
                        ->exists();
                    if ($dup) {
                        throw ValidationException::withMessages(['full_name' => ['Nama dan No. KK ini sudah terdaftar.']]);
                    }
                }
                if ($m) {
                    $data['version'] = $m->version + 1;
                }

                return $data;
            },
            'before_delete' => function (Model $m) {
                if ($m->user_id !== null) {
                    abort(409, 'Warga sudah memiliki akun. Nonaktifkan akunnya melalui Manajemen Pengguna.');
                }
            },
            'actions' => [
                'verify' => ['ability' => 'update', 'handler' => function (Citizen $c, Request $r, User $actor) {
                    $c->update(['status_warga' => 'tetap', 'verification_notes' => 'Diverifikasi oleh '.$actor->name, 'version' => $c->version + 1]);
                    self::notify()->to($c->user, 'Data kependudukan terverifikasi', 'Pengurus RT telah memverifikasi data Anda. Semua layanan kini tersedia.', '/dashboard', 'success');

                    return $c;
                }],
                'reject' => ['ability' => 'update', 'handler' => function (Citizen $c, Request $r, User $actor) {
                    $reason = (string) $r->input('reason', 'Berkas tidak sesuai.');
                    $c->update(['status_warga' => 'pindah', 'is_active' => false, 'verification_notes' => $reason, 'version' => $c->version + 1]);
                    $c->user?->update(['is_active' => false]);
                    self::notify()->to($c->user, 'Pendaftaran ditolak', $reason, '/login', 'danger');

                    return $c;
                }],
            ],
        ];
    }

    /* ------------------------------------------------------------------ */
    /* Konten komunitas */
    /* ------------------------------------------------------------------ */
    protected static function announcements(): array
    {
        $manage = fn (User $u) => $u->hasPermission('announcement.manage');

        return [
            'model' => Announcement::class,
            'search' => ['title', 'content'],
            'abilities' => ['read' => true, 'create' => 'announcement.manage', 'update' => 'announcement.manage', 'delete' => 'announcement.manage'],
            'query' => fn (Builder $q, User $u) => $q->when(! $manage($u), fn ($x) => $x->where('is_published', true))->orderByDesc('is_pinned')->latest('updated_at'),
            'transform' => fn (Announcement $a, User $u) => [
                'id' => $a->id,
                'title' => $a->title,
                'category' => $a->category,
                'content' => $a->content,
                'is_pinned' => (bool) $a->is_pinned,
                'is_published' => (bool) $a->is_published,
                'published_at' => self::iso($a->published_at),
                'version' => $a->version,
                'updated_at' => self::iso($a->updated_at),
                'can_edit' => $manage($u),
                'can_delete' => $manage($u),
            ],
            'rules' => fn () => [
                'title' => ['required', 'string', 'max:255'],
                'category' => ['required', Rule::in(['info', 'kegiatan', 'penting', 'keuangan'])],
                'content' => ['required', 'string'],
                'is_pinned' => ['nullable', 'boolean'],
                'is_published' => ['nullable', 'boolean'],
            ],
            'prepare' => function (array $data, User $u, ?Model $m) {
                if (! empty($data['is_published']) && ! $m?->is_published) {
                    $data['published_at'] = now();
                }
                if ($m) {
                    $data['version'] = $m->version + 1;
                }

                return $data;
            },
            'after_store' => fn (Model $m) => $m->is_published
                ? self::notify()->toEveryone('Pengumuman baru', $m->title, '/#kegiatan', $m->category === 'penting' ? 'warning' : 'info') : null,
            'after_update' => fn (Model $m, User $u, array $old) => $m->is_published && ! ($old['is_published'] ?? false)
                ? self::notify()->toEveryone('Pengumuman baru', $m->title, '/#kegiatan') : null,
        ];
    }

    protected static function events(): array
    {
        return [
            'model' => CommunityEvent::class,
            'search' => ['title', 'location'],
            'abilities' => ['read' => true, 'create' => 'event.manage', 'update' => 'event.manage', 'delete' => 'event.manage'],
            'query' => fn (Builder $q, User $u) => $q->when(! $u->hasPermission('event.manage'), fn ($x) => $x->where('is_published', true))->orderByDesc('event_date'),
            'transform' => fn (CommunityEvent $e, User $u) => [
                'id' => $e->id,
                'title' => $e->title,
                'description' => $e->description,
                'location' => $e->location,
                'event_date' => $e->event_date?->toDateString(),
                'start_time' => $e->start_time ? substr((string) $e->start_time, 0, 5) : null,
                'end_time' => $e->end_time ? substr((string) $e->end_time, 0, 5) : null,
                'is_published' => (bool) $e->is_published,
                'version' => $e->version,
                'updated_at' => self::iso($e->updated_at),
                'can_edit' => $u->hasPermission('event.manage'),
                'can_delete' => $u->hasPermission('event.manage'),
            ],
            'rules' => fn () => [
                'title' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'location' => ['required', 'string', 'max:255'],
                'event_date' => ['required', 'date'],
                'start_time' => ['nullable', 'date_format:H:i'],
                'end_time' => ['nullable', 'date_format:H:i'],
                'is_published' => ['nullable', 'boolean'],
            ],
            'prepare' => function (array $data, User $u, ?Model $m) {
                if ($m) {
                    $data['version'] = $m->version + 1;
                }

                return $data;
            },
            'after_store' => fn (Model $m) => $m->is_published
                ? self::notify()->toEveryone('Agenda baru', $m->title.' — '.$m->event_date->translatedFormat('d M Y'), '/#kegiatan') : null,
        ];
    }

    protected static function officers(): array
    {
        return [
            'model' => RtOfficer::class,
            'search' => ['name', 'position'],
            'abilities' => ['read' => true, 'create' => 'officer.manage', 'update' => 'officer.manage', 'delete' => 'officer.manage'],
            'query' => fn (Builder $q) => $q->orderBy('order_index'),
            'transform' => fn (RtOfficer $o, User $u) => [
                'id' => $o->id,
                'name' => $o->name,
                'position' => $o->position,
                'phone' => $o->phone,
                'order_index' => $o->order_index,
                'is_active' => (bool) $o->is_active,
                'updated_at' => self::iso($o->updated_at),
                'can_edit' => $u->hasPermission('officer.manage'),
                'can_delete' => $u->hasPermission('officer.manage'),
            ],
            'rules' => fn () => [
                'name' => ['required', 'string', 'max:255'],
                'position' => ['required', 'string', 'max:255'],
                'phone' => ['nullable', 'string', 'max:30'],
                'order_index' => ['nullable', 'integer', 'min:0'],
                'is_active' => ['nullable', 'boolean'],
            ],
            'prepare' => fn (array $data) => $data + ['order_index' => 0],
        ];
    }

    protected static function schedules(): array
    {
        return [
            'model' => RoundSchedule::class,
            'search' => ['day_of_week', 'pos_location'],
            'abilities' => ['read' => true, 'create' => 'round_schedule.manage', 'update' => 'round_schedule.manage', 'delete' => 'round_schedule.manage'],
            'query' => fn (Builder $q) => $q->orderByRaw(self::orderField('day_of_week', ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'])),
            'transform' => fn (RoundSchedule $s, User $u) => [
                'id' => $s->id,
                'day_of_week' => $s->day_of_week,
                'shift_name' => $s->shift_name,
                'officer_names' => implode(', ', (array) $s->officer_names),
                'pos_location' => $s->pos_location,
                'notes' => $s->notes,
                'is_active' => (bool) $s->is_active,
                'updated_at' => self::iso($s->updated_at),
                'can_edit' => $u->hasPermission('round_schedule.manage'),
                'can_delete' => $u->hasPermission('round_schedule.manage'),
            ],
            'rules' => fn () => [
                'day_of_week' => ['required', Rule::in(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'])],
                'shift_name' => ['required', 'string', 'max:100'],
                'officer_names' => ['required', 'string', 'max:500'],
                'pos_location' => ['required', 'string', 'max:255'],
                'notes' => ['nullable', 'string'],
                'is_active' => ['nullable', 'boolean'],
            ],
            'prepare' => function (array $data) {
                $data['officer_names'] = array_values(array_filter(array_map('trim', explode(',', $data['officer_names']))));

                return $data;
            },
        ];
    }

    protected static function contacts(): array
    {
        return [
            'model' => EmergencyContact::class,
            'search' => ['name', 'category', 'phone_number'],
            'abilities' => ['read' => true, 'create' => 'emergency.manage', 'update' => 'emergency.manage', 'delete' => 'emergency.manage'],
            'query' => fn (Builder $q) => $q->orderBy('order_index'),
            'transform' => fn (EmergencyContact $c, User $u) => [
                'id' => $c->id,
                'name' => $c->name,
                'category' => $c->category,
                'phone_number' => $c->phone_number,
                'description' => $c->description,
                'order_index' => $c->order_index,
                'is_active' => (bool) $c->is_active,
                'updated_at' => self::iso($c->updated_at),
                'can_edit' => $u->hasPermission('emergency.manage'),
                'can_delete' => $u->hasPermission('emergency.manage'),
            ],
            'rules' => fn () => [
                'name' => ['required', 'string', 'max:255'],
                'category' => ['required', 'string', 'max:100'],
                'phone_number' => ['required', 'string', 'max:30'],
                'description' => ['nullable', 'string', 'max:255'],
                'order_index' => ['nullable', 'integer', 'min:0'],
                'is_active' => ['nullable', 'boolean'],
            ],
            'prepare' => fn (array $data) => $data + ['order_index' => 0],
        ];
    }

    /* ------------------------------------------------------------------ */
    /* Surat pengantar */
    /* ------------------------------------------------------------------ */
    protected static function letters(): array
    {
        $isStaff = fn (User $u) => $u->hasPermission('letter.read') || $u->hasPermission('letter.verify') || $u->hasPermission('letter.approve');
        $isOwner = fn (User $u, Letter $l) => $u->citizen && $u->citizen->id === $l->citizen_id;

        $act = function (string $transition) {
            return function (Letter $l, Request $r, User $actor) use ($transition) {
                $svc = app(LetterService::class);
                $version = (int) $r->input('version', $l->version);

                $updated = match ($transition) {
                    'submit' => $svc->submit($l, $actor, $version),
                    'verify' => $svc->verify($l, $actor, $version, $r->input('notes')),
                    'approve' => $svc->approve($l, $actor, $version),
                    'complete' => $svc->complete($l, $actor, $version),
                    'reject' => $svc->reject($l, $actor, $version, (string) $r->validate(['reason' => ['required', 'string', 'max:500']])['reason']),
                };

                $applicant = $updated->citizen?->user;
                $ticket = $updated->ticket_number;

                match ($transition) {
                    'submit' => self::notify()->toRoles([UserRole::SEKRETARIS], 'Surat baru perlu verifikasi', "{$ticket} dari {$updated->citizen?->full_name}", '/app/letters', 'warning'),
                    'verify' => [
                        self::notify()->to($applicant, 'Surat diverifikasi', "{$ticket} lolos verifikasi sekretaris, menunggu pengesahan Ketua RT.", '/app/my-letters'),
                        self::notify()->toRoles([UserRole::KETUA_RT], 'Surat menunggu pengesahan', "{$ticket} sudah diverifikasi sekretaris.", '/app/letters', 'warning'),
                    ],
                    'approve' => self::notify()->to($applicant, 'Surat disahkan', "{$ticket} telah disahkan Ketua RT. Nomor: {$updated->letter_number}", '/app/my-letters', 'success'),
                    'complete' => self::notify()->to($applicant, 'Surat siap', "{$ticket} selesai dan siap digunakan.", '/app/my-letters', 'success'),
                    'reject' => self::notify()->to($applicant, 'Surat ditolak', "{$ticket}: ".$updated->rejection_reason, '/app/my-letters', 'danger'),
                };

                return $updated;
            };
        };

        $can = fn (string $perm) => fn (User $u, Letter $l) => $u->hasPermission($perm);

        return [
            'model' => Letter::class,
            'search' => ['ticket_number', 'type', 'keperluan', 'letter_number'],
            'abilities' => [
                'read' => true,
                'create' => fn (User $u) => $u->citizen !== null || $u->isSuperadmin(),
                'update' => fn (User $u, ?Letter $l) => $l ? ($isOwner($u, $l) && $l->status === 'draft') || $u->isSuperadmin() : true,
                'delete' => fn (User $u, ?Letter $l) => $l ? ($isOwner($u, $l) && $l->status === 'draft') || $u->isSuperadmin() : true,
            ],
            'with' => ['citizen', 'letterType', 'attachments'],
            'query' => function (Builder $q, User $u, Request $r) use ($isStaff) {
                if ($r->boolean('mine') || ! $isStaff($u)) {
                    $q->where('citizen_id', $u->citizen?->id ?? 0);
                }

                return $q->latest('updated_at');
            },
            'filters' => ['status'],
            'transform' => fn (Letter $l, User $u) => [
                'id' => $l->id,
                'ticket_number' => $l->ticket_number,
                'type' => $l->letterType?->nama_surat ?? $l->type,
                'jenis_surat_id' => $l->jenis_surat_id,
                'keperluan' => $l->keperluan,
                'status' => $l->status,
                'applicant' => $l->citizen?->full_name,
                'letter_number' => $l->letter_number,
                'catatan_admin' => $l->catatan_admin,
                'rejection_reason' => $l->rejection_reason,
                'attachments' => $l->attachments->map(fn ($a) => ['id' => $a->id, 'name' => $a->judul_lampiran, 'url' => url('/api/v1/files/letter-attachments/'.$a->id)])->values(),
                'can_download' => in_array($l->status, ['approved', 'completed'], true),
                'download_url' => in_array($l->status, ['approved', 'completed'], true) ? url("/api/v1/letters/{$l->id}/download?format=pdf") : null,
                'preview_url' => url("/api/v1/letters/{$l->id}/preview"),
                'verification_url' => $l->verification_token ? url('/api/v1/public/letter/verify/'.$l->verification_token) : null,
                'version' => $l->version,
                'created_at' => self::iso($l->created_at),
                'updated_at' => self::iso($l->updated_at),
                'can_edit' => $isOwner($u, $l) && $l->status === 'draft',
                'can_delete' => ($isOwner($u, $l) && $l->status === 'draft') || $u->isSuperadmin(),
                'can_submit' => $isOwner($u, $l) && $l->status === 'draft',
                'can_verify' => $l->status === 'submitted' && $u->hasPermission('letter.verify'),
                'can_approve' => $l->status === 'verified' && $u->hasPermission('letter.approve'),
                'can_complete' => $l->status === 'approved' && ($u->hasPermission('letter.complete') || $u->hasPermission('letter.approve')),
                'can_reject' => in_array($l->status, ['submitted', 'verified'], true) && ($u->hasPermission('letter.reject') || $u->hasPermission('letter.verify') || $u->hasPermission('letter.approve')),
            ],
            'rules' => fn () => [
                'jenis_surat_id' => ['required', 'integer', Rule::exists('jenis_surat', 'id')],
                'keperluan' => ['required', 'string', 'max:500'],
                'submit' => ['nullable', 'boolean'],
            ],
            'store' => function (array $data, User $u) {
                $citizen = $u->citizen;
                if (! $citizen) {
                    abort(422, 'Akun Anda belum terhubung ke data warga.');
                }
                $type = LetterType::query()->findOrFail($data['jenis_surat_id']);
                $svc = app(LetterService::class);
                $letter = $svc->create([
                    'citizen_id' => $citizen->id,
                    'jenis_surat_id' => $type->id,
                    'type' => $type->nama_surat,
                    'keperluan' => $data['keperluan'],
                ], $u);

                if (! empty($data['submit'])) {
                    $letter = $svc->submit($letter, $u, $letter->version);
                    self::notify()->toRoles([UserRole::SEKRETARIS], 'Surat baru perlu verifikasi', "{$letter->ticket_number} dari {$citizen->full_name}", '/app/letters', 'warning');
                }

                return $letter;
            },
            'prepare' => function (array $data, User $u, ?Model $m) {
                if ($m) {
                    unset($data['submit']);
                }
                if ($m && isset($data['jenis_surat_id'])) {
                    $data['type'] = LetterType::query()->find($data['jenis_surat_id'])?->nama_surat ?? $m->type;
                    $data['version'] = $m->version + 1;
                }

                return $data;
            },
            'before_delete' => function (Letter $l, User $u) {
                if ($l->status !== 'draft' && ! $u->isSuperadmin()) {
                    abort(409, 'Hanya surat berstatus draft yang dapat dihapus.');
                }
            },
            'actions' => [
                'submit' => ['ability' => fn (User $u, Letter $l) => $isOwner($u, $l), 'handler' => $act('submit')],
                'verify' => ['ability' => $can('letter.verify'), 'handler' => $act('verify')],
                'approve' => ['ability' => $can('letter.approve'), 'handler' => $act('approve')],
                'complete' => ['ability' => fn (User $u) => $u->hasPermission('letter.complete') || $u->hasPermission('letter.approve'), 'handler' => $act('complete')],
                'reject' => ['ability' => fn (User $u) => $u->hasPermission('letter.reject') || $u->hasPermission('letter.verify') || $u->hasPermission('letter.approve'), 'handler' => $act('reject')],
            ],
        ];
    }

    /* ------------------------------------------------------------------ */
    /* Pengaduan */
    /* ------------------------------------------------------------------ */
    protected static function complaints(): array
    {
        $isStaff = fn (User $u) => $u->hasPermission('complaint.read') || $u->hasPermission('complaint.manage');
        $isOwner = fn (User $u, Complaint $c) => $c->user_id === $u->id;

        return [
            'model' => Complaint::class,
            'search' => ['ticket_number', 'title', 'description', 'lokasi'],
            'abilities' => [
                'read' => true,
                'create' => true,
                'update' => fn (User $u, ?Complaint $c) => ! $c || $u->hasPermission('complaint.manage') || ($isOwner($u, $c) && $c->status === 'submitted'),
                'delete' => fn (User $u, ?Complaint $c) => ! $c || $u->hasPermission('complaint.manage') || ($isOwner($u, $c) && $c->status === 'submitted'),
            ],
            'query' => function (Builder $q, User $u, Request $r) use ($isStaff) {
                if ($r->boolean('mine') || ! $isStaff($u)) {
                    $q->where('user_id', $u->id);
                }

                return $q->latest('updated_at');
            },
            'filters' => ['status', 'priority'],
            'transform' => fn (Complaint $c, User $u) => [
                'id' => $c->id,
                'ticket_number' => $c->ticket_number,
                'kategori' => $c->kategori,
                'title' => $c->title,
                'description' => $c->description,
                'lokasi' => $c->lokasi,
                'status' => $c->status,
                'priority' => $c->priority,
                'admin_response' => $c->admin_response,
                'photo_url' => $c->attachment_path ? url('/api/v1/files/complaints/'.$c->id) : null,
                'reporter' => $c->is_anonymous && ! $u->hasPermission('complaint.manage') ? 'Anonim' : User::query()->find($c->user_id)?->name,
                'version' => $c->version,
                'created_at' => self::iso($c->created_at),
                'updated_at' => self::iso($c->updated_at),
                'can_edit' => $u->hasPermission('complaint.manage') || ($isOwner($u, $c) && $c->status === 'submitted'),
                'can_delete' => $u->hasPermission('complaint.manage') || ($isOwner($u, $c) && $c->status === 'submitted'),
            ],
            'rules' => function (Request $r, User $u, ?Model $m) {
                $staff = $m && $u->hasPermission('complaint.manage');

                return [
                    'title' => [$staff ? 'sometimes' : 'required', 'string', 'max:180'],
                    'description' => [$staff ? 'sometimes' : 'required', 'string'],
                    'kategori' => [$staff ? 'sometimes' : 'required', 'string', 'max:80'],
                    'lokasi' => ['nullable', 'string', 'max:255'],
                    'priority' => ['nullable', Rule::in(['rendah', 'sedang', 'tinggi', 'darurat'])],
                    'is_anonymous' => ['nullable', 'boolean'],
                    'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
                    'status' => ['nullable', Rule::in(['submitted', 'reviewed', 'processing', 'resolved', 'closed', 'rejected'])],
                    'admin_response' => ['nullable', 'string', 'max:2000'],
                ];
            },
            'store' => function (array $data, User $u) {
                unset($data['status'], $data['admin_response']);
                if (isset($data['attachment'])) {
                    $data['attachment_path'] = $data['attachment']->store('complaints', 'local');
                }
                unset($data['attachment']);
                $complaint = app(ComplaintService::class)->create($data, $u);
                self::notify()->toRoles([UserRole::KETUA_RT, UserRole::SEKRETARIS], 'Pengaduan baru', "{$complaint->ticket_number}: {$complaint->title}", '/app/complaints', $complaint->priority === 'darurat' ? 'danger' : 'warning');

                return $complaint;
            },
            'update' => function (Complaint $c, array $data, User $u) use ($isOwner) {
                unset($data['attachment']);
                if ($u->hasPermission('complaint.manage') && ! $isOwner($u, $c)) {
                    $status = $data['status'] ?? $c->status;
                    $updated = app(ComplaintService::class)->updateStatus($c, $status, $data, $u);
                    self::notify()->to($c->user_id, 'Pengaduan diperbarui', "{$c->ticket_number} kini berstatus {$status}.", '/app/my-complaints', in_array($status, ['resolved', 'closed'], true) ? 'success' : 'info');

                    return $updated;
                }
                unset($data['status'], $data['admin_response'], $data['priority'], $data['attachment']);
                $c->update($data + ['version' => $c->version + 1]);

                return $c;
            },
        ];
    }

    /* ------------------------------------------------------------------ */
    /* Laporan keamanan */
    /* ------------------------------------------------------------------ */
    protected static function securityReports(): array
    {
        $isStaff = fn (User $u) => $u->hasPermission('security.read') || $u->hasPermission('security.manage');
        $isOwner = fn (User $u, SecurityReport $s) => $s->reporter_user_id === $u->id;

        return [
            'model' => SecurityReport::class,
            'search' => ['ticket_number', 'title', 'location', 'description'],
            'abilities' => [
                'read' => true,
                'create' => true,
                'update' => fn (User $u, ?SecurityReport $s) => ! $s || $u->hasPermission('security.manage') || ($isOwner($u, $s) && $s->status === 'submitted'),
                'delete' => fn (User $u, ?SecurityReport $s) => ! $s || $u->hasPermission('security.manage') || ($isOwner($u, $s) && $s->status === 'submitted'),
            ],
            'query' => function (Builder $q, User $u, Request $r) use ($isStaff) {
                if ($r->boolean('mine') || ! $isStaff($u)) {
                    $q->where('reporter_user_id', $u->id);
                }

                return $q->latest('updated_at');
            },
            'filters' => ['status', 'severity'],
            'transform' => fn (SecurityReport $s, User $u) => [
                'id' => $s->id,
                'ticket_number' => $s->ticket_number,
                'category' => $s->category,
                'severity' => $s->severity,
                'title' => $s->title,
                'description' => $s->description,
                'location' => $s->location,
                'incident_at' => self::iso($s->incident_at),
                'status' => $s->status,
                'resolution' => $s->resolution,
                'version' => $s->version,
                'updated_at' => self::iso($s->updated_at),
                'can_edit' => $u->hasPermission('security.manage') || ($isOwner($u, $s) && $s->status === 'submitted'),
                'can_delete' => $u->hasPermission('security.manage') || ($isOwner($u, $s) && $s->status === 'submitted'),
            ],
            'rules' => function (Request $r, User $u, ?Model $m) {
                $staff = $m && $u->hasPermission('security.manage');

                return [
                    'category' => [$staff ? 'sometimes' : 'required', 'string', 'max:50'],
                    'severity' => ['nullable', Rule::in(['low', 'medium', 'high', 'emergency'])],
                    'title' => [$staff ? 'sometimes' : 'required', 'string', 'max:180'],
                    'description' => [$staff ? 'sometimes' : 'required', 'string'],
                    'location' => [$staff ? 'sometimes' : 'required', 'string', 'max:255'],
                    'incident_at' => ['nullable', 'date'],
                    'status' => ['nullable', Rule::in(['submitted', 'reviewed', 'assigned', 'processing', 'resolved', 'closed'])],
                    'resolution' => ['nullable', 'string', 'max:2000'],
                ];
            },
            'store' => function (array $data, User $u) {
                unset($data['status'], $data['resolution']);
                $data['incident_at'] = $data['incident_at'] ?? now();
                $report = app(SecurityReportService::class)->create($data, $u);
                self::notify()->toRoles([UserRole::PETUGAS_KEAMANAN, UserRole::KETUA_RT], 'Laporan keamanan baru', "{$report->ticket_number}: {$report->title}", '/app/security', $report->severity === 'emergency' ? 'danger' : 'warning');

                return $report;
            },
            'update' => function (SecurityReport $s, array $data, User $u) use ($isOwner) {
                if ($u->hasPermission('security.manage') && ! $isOwner($u, $s)) {
                    $payload = $data + ['version' => $s->version + 1];
                    if (in_array($data['status'] ?? null, ['resolved', 'closed'], true)) {
                        $payload['resolved_at'] = now();
                    }
                    $s->update($payload);
                    self::notify()->to($s->reporter_user_id, 'Laporan keamanan diperbarui', "{$s->ticket_number} kini berstatus {$s->status}.", '/app/my-security');

                    return $s;
                }
                unset($data['status'], $data['resolution'], $data['severity']);
                $s->update($data + ['version' => $s->version + 1]);

                return $s;
            },
        ];
    }

    /* ------------------------------------------------------------------ */
    /* Buku kas */
    /* ------------------------------------------------------------------ */
    protected static function finance(): array
    {
        return [
            'model' => FinanceTransaction::class,
            'search' => ['transaction_number', 'category', 'description'],
            'abilities' => [
                'read' => 'finance.transaction.read',
                'create' => 'finance.transaction.create',
                'update' => fn (User $u, ?FinanceTransaction $t) => $u->hasPermission('finance.transaction.create') && (! $t || $t->status === 'draft'),
                'delete' => fn (User $u, ?FinanceTransaction $t) => $u->hasPermission('finance.transaction.create') && (! $t || $t->status === 'draft'),
            ],
            'query' => fn (Builder $q) => $q->orderByDesc('transaction_date')->orderByDesc('id'),
            'filters' => ['status', 'type'],
            'transform' => fn (FinanceTransaction $t, User $u) => [
                'id' => $t->id,
                'transaction_number' => $t->transaction_number,
                'type' => $t->type,
                'category' => $t->category,
                'amount' => (int) $t->amount_idr,
                'description' => $t->description,
                'transaction_date' => $t->transaction_date?->toDateString(),
                'status' => $t->status,
                'reversal_reason' => $t->reversal_reason,
                'version' => $t->version,
                'updated_at' => self::iso($t->updated_at),
                'can_edit' => $t->status === 'draft' && $u->hasPermission('finance.transaction.create'),
                'can_delete' => $t->status === 'draft' && $u->hasPermission('finance.transaction.create'),
                'can_publish' => $t->status === 'draft' && $u->hasPermission('finance.transaction.publish'),
                'can_reverse' => $t->status === 'published' && $u->hasPermission('finance.transaction.reverse'),
            ],
            'rules' => fn () => [
                'type' => ['required', Rule::in(['income', 'expense'])],
                'category' => ['required', 'string', 'max:100'],
                'amount' => ['required', 'integer', 'min:1', 'max:999999999999'],
                'description' => ['nullable', 'string', 'max:500'],
                'transaction_date' => ['required', 'date'],
            ],
            'store' => function (array $data, User $u) {
                $data['amount_idr'] = (int) $data['amount'];
                unset($data['amount']);

                return app(FinanceService::class)->create($data, $u);
            },
            'prepare' => function (array $data, User $u, ?Model $m) {
                if ($m) {
                    $data['amount_idr'] = (int) $data['amount'];
                    $data['amount'] = (float) $data['amount_idr'];
                    $data['version'] = $m->version + 1;
                }

                return $data;
            },
            'destroy' => fn (FinanceTransaction $t) => app(FinanceService::class)->delete($t),
            'after_store' => fn (Model $m, User $u) => self::notify()->toRoles([UserRole::KETUA_RT], 'Transaksi kas baru', ($m->type === 'income' ? 'Pemasukan' : 'Pengeluaran').' Rp '.number_format($m->amount_idr, 0, ',', '.').' — '.$m->category, '/app/finance'),
            'actions' => [
                'publish' => ['ability' => 'finance.transaction.publish', 'handler' => function (FinanceTransaction $t, Request $r) {
                    $published = app(FinanceService::class)->publish($t, (int) $r->input('version', $t->version));
                    self::notify()->toEveryone('Laporan kas diperbarui', ($published->type === 'income' ? 'Pemasukan' : 'Pengeluaran').' Rp '.number_format($published->amount_idr, 0, ',', '.').' — '.$published->category, '/#keuangan');

                    return $published;
                }],
                'reverse' => ['ability' => 'finance.transaction.reverse', 'handler' => function (FinanceTransaction $t, Request $r) {
                    $reason = (string) $r->validate(['reason' => ['required', 'string', 'max:300']])['reason'];

                    return app(FinanceService::class)->reverse($t, (int) $r->input('version', $t->version), $reason);
                }],
            ],
        ];
    }

    /* ------------------------------------------------------------------ */
    /* Iuran warga */
    /* ------------------------------------------------------------------ */
    protected static function dues(): array
    {
        $isStaff = fn (User $u) => $u->hasPermission('finance.dues.read') || $u->hasPermission('finance.dues.manage');
        $months = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        return [
            'model' => ResidentDue::class,
            'search' => ['notes'],
            'abilities' => [
                'read' => true,
                'create' => 'finance.dues.manage',
            ],
            'with' => ['citizen', 'payments'],
            'query' => function (Builder $q, User $u, Request $r) use ($isStaff) {
                if ($r->boolean('mine') || ! $isStaff($u)) {
                    $q->where('citizen_id', $u->citizen?->id ?? 0);
                }

                return $q->orderByDesc('period_year')->orderByDesc('period_month')->orderBy('citizen_id');
            },
            'filters' => ['period_year', 'period_month'],
            'transform' => function (ResidentDue $d, User $u) use ($months) {
                $paid = $d->getTotalPaidIdr();

                return [
                    'id' => $d->id,
                    'citizen' => $d->citizen?->full_name,
                    'period' => ($months[$d->period_month] ?? $d->period_month).' '.$d->period_year,
                    'amount_due' => $d->amount_due_idr,
                    'amount_paid' => $paid,
                    'remaining' => max(0, $d->amount_due_idr - $paid),
                    'status' => strtolower($d->status),
                    'due_date' => $d->due_date?->toDateString(),
                    'receipts' => $d->payments->where('status', 'PAID')->pluck('receipt_number')->values(),
                    'updated_at' => self::iso($d->updated_at),
                    'can_pay' => $d->status !== 'PAID' && $u->hasPermission('finance.payment.manage'),
                ];
            },
            'rules' => fn () => [
                'period_year' => ['required', 'integer', 'min:2020', 'max:2100'],
                'period_month' => ['required', 'integer', 'between:1,12'],
                'amount_due_idr' => ['required', 'integer', 'min:1000', 'max:100000000'],
                'notes' => ['nullable', 'string', 'max:200'],
            ],
            'store' => function (array $data, User $u) {
                $count = app(ResidentDueService::class)->generateMonthlyDues($data['period_year'], $data['period_month'], $data['amount_due_idr'], null, $data['notes'] ?? null);
                if ($count === 0) {
                    abort(409, 'Tagihan periode ini sudah pernah dibuat untuk semua warga.');
                }
                self::notify()->toRoles([UserRole::WARGA], 'Tagihan iuran baru', 'Iuran '.$data['period_month'].'/'.$data['period_year'].' sebesar Rp '.number_format($data['amount_due_idr'], 0, ',', '.'), '/app/my-dues', 'warning');

                return ResidentDue::query()->where('period_year', $data['period_year'])->where('period_month', $data['period_month'])->latest('id')->firstOrFail();
            },
            'actions' => [
                'pay' => ['ability' => 'finance.payment.manage', 'handler' => function (ResidentDue $d, Request $r, User $actor) {
                    $data = $r->validate([
                        'amount' => ['required', 'integer', 'min:1'],
                        'payment_method' => ['nullable', Rule::in(['cash', 'transfer', 'qris'])],
                        'notes' => ['nullable', 'string', 'max:200'],
                    ]);
                    $remaining = max(0, $d->amount_due_idr - $d->getTotalPaidIdr());
                    if ($remaining === 0) {
                        abort(409, 'Tagihan ini sudah lunas.');
                    }
                    if ($data['amount'] > $remaining) {
                        throw ValidationException::withMessages(['amount' => ['Nominal melebihi sisa tagihan (Rp '.number_format($remaining, 0, ',', '.').').']]);
                    }
                    $payment = app(ResidentDueService::class)->recordPayment($d, $data['amount'], $data['payment_method'] ?? 'cash', $actor, $data['notes'] ?? null);
                    self::notify()->to($d->citizen?->user, 'Pembayaran iuran diterima', 'Rp '.number_format($data['amount'], 0, ',', '.').' · No. kuitansi '.$payment->receipt_number, '/app/my-dues', 'success');

                    return $d->refresh();
                }],
            ],
        ];
    }

    protected static function auditLogs(): array
    {
        return [
            'model' => AuditLog::class,
            'search' => ['action', 'entity_type', 'ip_address'],
            'abilities' => ['read' => 'audit.read'],
            'with' => ['user'],
            'query' => fn (Builder $q) => $q->latest('id'),
            'transform' => fn (AuditLog $a) => [
                'id' => $a->id,
                'action' => $a->action,
                'entity' => $a->entity_type.' #'.$a->entity_id,
                'user' => $a->user?->name ?? 'Sistem',
                'ip_address' => $a->ip_address,
                'created_at' => self::iso($a->created_at),
            ],
        ];
    }
}
