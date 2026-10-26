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
     * Tingkat hak akses: superadmin > admin (semua pengurus) > user (warga).
     */
    public function tier(): string
    {
        return match ($this) {
            self::SUPERADMIN => 'superadmin',
            self::WARGA => 'user',
            default => 'admin',
        };
    }

    public function isStaff(): bool
    {
        return $this !== self::WARGA;
    }

    /**
     * Peran yang boleh ditetapkan oleh peran ini lewat manajemen pengguna.
     *
     * @return array<int, self>
     */
    public function assignableRoles(): array
    {
        return match ($this) {
            self::SUPERADMIN => self::cases(),
            self::ADMIN => [self::KETUA_RT, self::SEKRETARIS, self::BENDAHARA, self::PETUGAS_KEAMANAN, self::WARGA],
            default => [],
        };
    }
}
