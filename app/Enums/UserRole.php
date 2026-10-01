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

    public function label(): string
    {
        return match ($this) {
            self::SUPERADMIN => 'Super Administrator',
            self::ADMIN => 'Admin / Pengurus RT',
            self::WARGA => 'Warga Lingkungan',
            self::KETUA_RT => 'Ketua RT',
            self::BENDAHARA => 'Bendahara RT',
            self::SEKRETARIS => 'Sekretaris RT',
        };
    }
}
