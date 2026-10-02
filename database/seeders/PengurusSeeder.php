<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\RtOfficer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PengurusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = Hash::make('password');

        User::updateOrCreate(
            ['email' => 'ketua_rt@warga.local'],
            [
                'name' => 'Bapak Ketua RT',
                'password' => $password,
                'role' => UserRole::KETUA_RT,
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'bendahara@warga.local'],
            [
                'name' => 'Bapak Bendahara',
                'password' => $password,
                'role' => UserRole::BENDAHARA,
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'sekretaris@warga.local'],
            [
                'name' => 'Bapak Sekretaris',
                'password' => $password,
                'role' => UserRole::SEKRETARIS,
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'keamanan@warga.local'],
            [
                'name' => 'Bapak Petugas Keamanan',
                'password' => $password,
                'role' => UserRole::PETUGAS_KEAMANAN,
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'admin@warga.local'],
            [
                'name' => 'Administrator RT',
                'password' => $password,
                'role' => UserRole::ADMIN,
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'warga@warga.local'],
            [
                'name' => 'Bapak Warga Teladan',
                'password' => $password,
                'role' => UserRole::WARGA,
                'is_active' => true,
            ]
        );

        // Sinkronisasi Data Pejabat RT (RtOfficer)
        RtOfficer::updateOrCreate(
            ['name' => 'Bapak Ketua RT'],
            [
                'position' => 'Ketua RT 01 / RW 05',
                'phone' => '081234567891',
                'order_index' => 1,
                'is_active' => true,
            ]
        );

        RtOfficer::updateOrCreate(
            ['name' => 'Bapak Sekretaris'],
            [
                'position' => 'Sekretaris RT 01',
                'phone' => '081234567892',
                'order_index' => 2,
                'is_active' => true,
            ]
        );

        RtOfficer::updateOrCreate(
            ['name' => 'Bapak Bendahara'],
            [
                'position' => 'Bendahara RT 01',
                'phone' => '081234567893',
                'order_index' => 3,
                'is_active' => true,
            ]
        );

        RtOfficer::updateOrCreate(
            ['name' => 'Bapak Petugas Keamanan'],
            [
                'position' => 'Seksi Keamanan & Ketertiban',
                'phone' => '081234567894',
                'order_index' => 4,
                'is_active' => true,
            ]
        );
    }
}
