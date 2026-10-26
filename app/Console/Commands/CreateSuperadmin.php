<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateSuperadmin extends Command
{
    protected $signature = 'app:create-superadmin {username : Username login (4-30 huruf kecil/angka/titik/garis bawah)}
        {--name=Super Administrator : Nama tampilan}
        {--email= : Email (opsional, dipakai untuk reset password)}
        {--password= : Password (kosongkan agar diminta interaktif)}';

    protected $description = 'Membuat akun Superadmin pertama di production (seeder demo tidak berjalan di production)';

    public function handle(): int
    {
        $password = $this->option('password') ?: $this->secret('Password (min. 8 karakter)');

        $validator = Validator::make([
            'username' => strtolower((string) $this->argument('username')),
            'email' => $this->option('email') ?: null,
            'password' => $password,
        ], [
            'username' => ['required', 'regex:/^[a-z0-9_.]{4,30}$/', 'unique:users,username'],
            'email' => ['nullable', 'email', 'unique:users,email'],
            'password' => ['required', Password::min(8)],
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
            'role' => UserRole::SUPERADMIN,
            'is_active' => true,
        ]);

        $this->info("Superadmin '{$user->username}' dibuat. Login di /login lalu segera ganti password di menu Profil.");

        return self::SUCCESS;
    }
}
