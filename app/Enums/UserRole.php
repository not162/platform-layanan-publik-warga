<?php

namespace App\Enums;

enum UserRole: string
{
    case SUPERADMIN = 'SUPERADMIN';
    case ADMIN = 'ADMIN';
    case WARGA = 'WARGA';
    case KETUA_RT = 'KETUA_RT';
    case BENDAHARA = 'BENDAHARA';
    case SEKRETARIS = 'SEKRETARIS';
    case PETUGAS_KEAMANAN = 'PETUGAS_KEAMANAN';

    public function label(): string
    {
        return match ($this) {
            self::SUPERADMIN => 'Super Administrator',
            self::ADMIN => 'Admin / Pengurus RT',
            self::WARGA => 'Warga Lingkungan',
            self::KETUA_RT => 'Ketua RT',
            self::BENDAHARA => 'Bendahara RT',
            self::SEKRETARIS => 'Sekretaris RT',
            self::PETUGAS_KEAMANAN => 'Petugas Keamanan RT',
        };
    }

    /**
     * Tingkat hak akses: superadmin (Ketua RT & akun Superadmin sistem)
     * > admin (Sekretaris, Bendahara, pengurus lain) > user (warga).
     */
    public function tier(): string
    {
        return match ($this) {
            self::SUPERADMIN, self::KETUA_RT => 'superadmin',
            self::WARGA => 'user',
            default => 'admin',
        };
    }

    public function tierLabel(): string
    {
        return match ($this->tier()) {
            'superadmin' => 'Superadmin',
            'admin' => 'Admin',
            default => 'User (Warga)',
        };
    }

    public function isSuperadminTier(): bool
    {
        return $this->tier() === 'superadmin';
    }

    /**
     * Semua peran tingkat superadmin (penerima notifikasi pengesahan, dsb.).
     *
     * @return array<int, self>
     */
    public static function superadminTier(): array
    {
        return array_values(array_filter(self::cases(), fn (self $r) => $r->isSuperadminTier()));
    }

    public function isStaff(): bool
    {
        return $this !== self::WARGA;
    }

    /**
     * Peran yang boleh ditetapkan oleh peran ini lewat manajemen pengguna.
     * Tidak ada peran yang dapat menetapkan peran setingkat atau di atasnya,
     * kecuali akun Superadmin sistem.
     *
     * @return array<int, self>
     */
    public function assignableRoles(): array
    {
        return match ($this) {
            self::SUPERADMIN => self::cases(),
            self::KETUA_RT => [self::ADMIN, self::SEKRETARIS, self::BENDAHARA, self::PETUGAS_KEAMANAN, self::WARGA],
            self::ADMIN => [self::SEKRETARIS, self::BENDAHARA, self::PETUGAS_KEAMANAN, self::WARGA],
            default => [],
        };
    }
}
