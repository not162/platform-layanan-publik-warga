<?php

namespace App\Enums;

enum UserRole: string
{
    case SUPERADMIN = 'SUPERADMIN';
    case ADMIN = 'ADMIN';
    case WARGA = 'WARGA';

    public function label(): string
    {
        return match ($this) {
            self::SUPERADMIN => 'Super Administrator',
            self::ADMIN => 'Admin / Pengurus RT',
            self::WARGA => 'Warga Lingkungan',
        };
    }
}
