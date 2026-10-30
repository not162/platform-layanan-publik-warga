<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CreateSuperadmin extends Command
{
    protected $signature = 'app:create-superadmin {username : Username login (4-30 huruf kecil/angka/titik/garis bawah)}
        {--name=Super Administrator : Nama tampilan}
        {--email= : Email (opsional, dipakai untuk reset password)}
        {--password= : Password (kosongkan agar diminta interaktif)}
        {--role=SUPERADMIN : Peran tingkat superadmin: SUPERADMIN atau KETUA_RT}';

    protected $description = 'Membuat akun tingkat Superadmin pertama (Superadmin sistem atau Ketua RT) di production (seeder demo tidak berjalan di production)';

    public function handle(): int
    {
        $password = $this->option('password') ?: $this->secret('Password (min. 8 karakter)');

        $validator = Validator::make([
            'username' => strtolower((string) $this->argument('username')),
            'email' => $this->option('email') ?: null,
            'password' => $password,
            'role' => strtoupper((string) $this->option('role')),
        ], [
            'username' => ['required', 'regex:/^[a-z0-9_.]{4,30}$/', 'unique:users,username'],
            'email' => ['nullable', 'email', 'unique:users,email'],
            'password' => ['required', Password::min(8)],
            'role' => ['required', Rule::in(array_map(fn (UserRole $r) => $r->value, UserRole::superadminTier()))],
        ], [
            'role.in' => 'Peran harus tingkat superadmin: SUPERADMIN atau KETUA_RT.',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $this->option('name'),
            'username' => strtolower((string) $this->argument('username')),
            'email' => $this->option('email') ?: null,
            'password' => $password,
            'role' => UserRole::from(strtoupper((string) $this->option('role'))),
            'is_active' => true,
        ]);

        $this->info("{$user->role->label()} '{$user->username}' dibuat. Login di /login lalu segera ganti password di menu Profil.");

        return self::SUCCESS;
    }
}
