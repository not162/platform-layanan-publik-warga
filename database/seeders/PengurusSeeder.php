<?php

namespace Database\Seeders;

use App\Enums\UserRole;
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
    }
}
