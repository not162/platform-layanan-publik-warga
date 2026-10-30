<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\User;

/**
 * Peta halaman aplikasi + hak akses per peran. Dipakai sidebar desktop, bottom-nav mobile, dan guard route.
 */
class Navigation
{
    /**
     * key => [label, icon, group, ability(callable|string|null)]
     *
     * @return array<string, array<string, mixed>>
     */
    public static function pages(): array
    {
        $any = fn (User $u, array $perms) => collect($perms)->contains(fn ($p) => $u->hasPermission($p));

        return [
            'dashboard' => ['label' => 'Beranda', 'icon' => 'home', 'group' => 'Utama', 'can' => fn () => true],
            'my-dues' => ['label' => 'Iuran Saya', 'short' => 'Iuran', 'icon' => 'wallet', 'group' => 'Layanan', 'can' => fn (User $u) => $u->isWarga()],
            'my-letters' => ['label' => 'Surat Saya', 'short' => 'Surat', 'icon' => 'mail', 'group' => 'Layanan', 'can' => fn (User $u) => $u->isWarga()],
            'my-complaints' => ['label' => 'Pengaduan Saya', 'short' => 'Aduan', 'icon' => 'alert', 'group' => 'Layanan', 'can' => fn (User $u) => $u->isWarga()],
            'my-security' => ['label' => 'Lapor Keamanan', 'short' => 'Keamanan', 'icon' => 'shield', 'group' => 'Layanan', 'can' => fn (User $u) => $u->isWarga()],
            'letters' => ['label' => 'Verifikasi & Pengesahan Surat', 'short' => 'Surat', 'icon' => 'mail', 'group' => 'Layanan Warga', 'can' => fn (User $u) => $any($u, ['letter.read', 'letter.verify', 'letter.approve'])],
            'complaints' => ['label' => 'Pengaduan Warga', 'short' => 'Aduan', 'icon' => 'alert', 'group' => 'Layanan Warga', 'can' => fn (User $u) => $any($u, ['complaint.read', 'complaint.manage'])],
            'security' => ['label' => 'Laporan Keamanan', 'short' => 'Keamanan', 'icon' => 'shield', 'group' => 'Layanan Warga', 'can' => fn (User $u) => $any($u, ['security.read', 'security.manage'])],
            'letter-types' => ['label' => 'Jenis & Syarat Surat', 'short' => 'Jenis Surat', 'icon' => 'list', 'group' => 'Layanan Warga', 'can' => fn (User $u) => $u->hasPermission('letter.template.manage')],
            'citizens' => ['label' => 'Data Warga', 'short' => 'Warga', 'icon' => 'users', 'group' => 'Kependudukan', 'can' => fn (User $u) => $u->hasPermission('citizen.read')],
            'family-cards' => ['label' => 'Kartu Keluarga', 'short' => 'KK', 'icon' => 'building', 'group' => 'Kependudukan', 'can' => fn (User $u) => $u->hasPermission('citizen.read')],
            'finance' => ['label' => 'Buku Kas RT', 'short' => 'Kas', 'icon' => 'wallet', 'group' => 'Keuangan', 'can' => fn (User $u) => $u->hasPermission('finance.transaction.read')],
            'dues' => ['label' => 'Iuran Warga', 'short' => 'Iuran', 'icon' => 'gift', 'group' => 'Keuangan', 'can' => fn (User $u) => $any($u, ['finance.dues.read', 'finance.dues.manage'])],
            'purchases' => ['label' => 'Belanja RT', 'short' => 'Belanja', 'icon' => 'list', 'group' => 'Keuangan', 'can' => fn (User $u) => $u->hasPermission('finance.purchase.read')],
            'reports' => ['label' => 'Laporan Keuangan', 'short' => 'Laporan', 'icon' => 'layers', 'group' => 'Keuangan', 'can' => fn (User $u) => $u->hasPermission('finance.report.read')],
            'announcements' => ['label' => 'Pengumuman', 'icon' => 'megaphone', 'group' => 'Konten', 'can' => fn (User $u) => $u->hasPermission('announcement.manage')],
            'events' => ['label' => 'Agenda Kegiatan', 'icon' => 'calendar', 'group' => 'Konten', 'can' => fn (User $u) => $u->hasPermission('event.manage')],
            'officers' => ['label' => 'Struktur Pengurus', 'icon' => 'badge', 'group' => 'Konten', 'can' => fn (User $u) => $u->hasPermission('officer.manage')],
            'schedules' => ['label' => 'Jadwal Ronda', 'icon' => 'moon', 'group' => 'Konten', 'can' => fn (User $u) => $u->hasPermission('round_schedule.manage')],
            'contacts' => ['label' => 'Kontak Darurat', 'icon' => 'phone', 'group' => 'Konten', 'can' => fn (User $u) => $u->hasPermission('emergency.manage')],
            'users' => ['label' => 'Manajemen Pengguna', 'short' => 'Pengguna', 'icon' => 'key', 'group' => 'Sistem', 'can' => fn (User $u) => $u->hasPermission('user.manage')],
            'audit' => ['label' => 'Audit Log', 'icon' => 'list', 'group' => 'Sistem', 'can' => fn (User $u) => $u->hasPermission('audit.read')],
            'system' => ['label' => 'Pemodelan Sistem', 'short' => 'Sistem', 'icon' => 'layers', 'group' => 'Sistem', 'can' => fn (User $u) => $u->role->isStaff()],
            'profile' => ['label' => 'Profil & Keamanan', 'short' => 'Profil', 'icon' => 'user', 'group' => 'Akun', 'can' => fn () => true],
        ];
    }

    public static function allowed(User $user, string $page): bool
    {
        $def = self::pages()[$page] ?? null;

        return $def !== null && (bool) $def['can']($user);
    }

    public static function canCreate(User $user, string $page): bool
    {
        return match ($page) {
            'users' => $user->hasPermission('user.manage'),
            'citizens', 'family-cards' => $user->hasPermission('citizen.manage'),
            'letter-types' => $user->hasPermission('letter.template.manage'),
            'purchases' => $user->hasPermission('finance.purchase.create'),
            'reports' => $user->hasPermission('finance.report.generate'),
            'announcements' => $user->hasPermission('announcement.manage'),
            'events' => $user->hasPermission('event.manage'),
            'officers' => $user->hasPermission('officer.manage'),
            'schedules' => $user->hasPermission('round_schedule.manage'),
            'contacts' => $user->hasPermission('emergency.manage'),
            'finance' => $user->hasPermission('finance.transaction.create'),
            'dues' => $user->hasPermission('finance.dues.manage'),
            'my-letters' => $user->citizen !== null,
            'my-complaints', 'my-security' => true,
            default => false,
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function for(User $user): array
    {
        $items = [];
        foreach (self::pages() as $key => $def) {
            if ($def['can']($user)) {
                $items[] = [
                    'key' => $key,
                    'label' => $def['label'],
                    'short' => $def['short'] ?? $def['label'],
                    'icon' => $def['icon'],
                    'group' => $def['group'],
                    'href' => $key === 'dashboard' ? route('dashboard') : ($key === 'profile' ? route('profile') : route('app.page', $key)),
                ];
            }
        }

        return $items;
    }

    /**
     * Item utama untuk bottom navigation mobile (maks. 4 + tombol "Menu"),
     * diurutkan menurut tugas utama tiap peran.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    public static function bottom(array $items, ?User $user = null): array
    {
        $priority = match ($user?->role) {
            UserRole::WARGA => ['dashboard', 'my-letters', 'my-dues', 'my-complaints', 'my-security'],
            UserRole::KETUA_RT => ['dashboard', 'letters', 'reports', 'complaints', 'users'],
            UserRole::SEKRETARIS => ['dashboard', 'letters', 'citizens', 'complaints'],
            UserRole::BENDAHARA => ['dashboard', 'finance', 'dues', 'purchases', 'reports'],
            UserRole::PETUGAS_KEAMANAN => ['dashboard', 'security', 'schedules', 'contacts'],
            default => ['dashboard', 'my-letters', 'letters', 'finance', 'my-complaints', 'complaints', 'citizens', 'users', 'security', 'my-security', 'announcements'],
        };
        $byKey = collect($items)->keyBy('key');

        return collect($priority)->map(fn ($k) => $byKey->get($k))->filter()->take(4)->values()->all();
    }
}
