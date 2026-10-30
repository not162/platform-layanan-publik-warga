<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Citizen;
use App\Models\FamilyCard;
use App\Models\RtOfficer;
use App\Models\User;
use Illuminate\Database\Seeder;

class PengurusSeeder extends Seeder
{
    /**
     * Akun demo (khusus lingkungan lokal/testing). Password semua akun: "password".
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $accounts = [
            ['superadmin', 'Super Administrator', UserRole::SUPERADMIN, 'superadmin@warga.local', null],
            ['admin', 'Administrator RT', UserRole::ADMIN, 'admin@warga.local', null],
            ['ketua_rt', config('rt.officers.ketua'), UserRole::KETUA_RT, 'ketua_rt@warga.local', 'Ketua RT '.config('rt.rt').' / RW '.config('rt.rw')],
            ['sekretaris', config('rt.officers.sekretaris'), UserRole::SEKRETARIS, 'sekretaris@warga.local', 'Sekretaris RT '.config('rt.rt')],
            ['bendahara', config('rt.officers.bendahara'), UserRole::BENDAHARA, 'bendahara@warga.local', 'Bendahara RT '.config('rt.rt')],
            ['keamanan', 'Bapak Petugas Keamanan', UserRole::PETUGAS_KEAMANAN, 'keamanan@warga.local', 'Seksi Keamanan & Ketertiban'],
        ];

        foreach ($accounts as $i => [$username, $name, $role, $email, $position]) {
            self::account($username, $email, ['name' => $name, 'role' => $role]);

            if ($position) {
                RtOfficer::updateOrCreate(
                    ['name' => $name],
                    ['position' => $position, 'phone' => '08123456789'.$i, 'order_index' => $i, 'is_active' => true]
                );
            }
        }

        // Warga demo yang sudah punya akun
        $card = FamilyCard::query()->firstOrCreate(
            ['no_kk_hash' => hash('sha256', '3171040409950001')],
            ['no_kk' => '3171040409950001', 'address' => 'Gg. Bugis No. 95, RT 04 / RW 09, Kemayoran', 'rt' => '004', 'rw' => '009', 'city' => 'Jakarta Pusat', 'district' => 'Kemayoran', 'version' => 1]
        );

        $citizen = Citizen::query()->firstOrCreate(
            ['nik_hash' => hash('sha256', '3171041203900001')],
            [
                'family_card_id' => $card->id, 'nik' => '3171041203900001', 'full_name' => 'Budi Santoso', 'gender' => 'Laki-laki',
                'place_of_birth' => 'Jakarta', 'date_of_birth' => '1990-03-12', 'phone' => '081200000001', 'status_warga' => 'tetap', 'version' => 1,
            ]
        );

        $warga = self::account('warga', 'warga@warga.local', ['name' => 'Budi Santoso', 'role' => UserRole::WARGA, 'warga_id' => $citizen->id]);
        Citizen::query()->where('user_id', $warga->id)->where('id', '!=', $citizen->id)->update(['user_id' => null]);
        $citizen->update(['user_id' => $warga->id]);

        // Warga master RT yang belum punya akun — dipakai untuk mencoba aktivasi akun lewat halaman Daftar
        Citizen::query()->firstOrCreate(
            ['nik_hash' => hash('sha256', '3171045505920002')],
            [
                'family_card_id' => $card->id, 'nik' => '3171045505920002', 'full_name' => 'Siti Aminah', 'gender' => 'Perempuan',
                'place_of_birth' => 'Bandung', 'date_of_birth' => '1992-05-15', 'status_warga' => 'tetap', 'version' => 1,
            ]
        );
    }

    /**
     * Akun demo dicocokkan lewat username ATAU email, karena migrasi username
     * mengisi akun lama dengan akhiran id (mis. "superadmin2").
     *
     * @param  array<string, mixed>  $attributes
     */
    protected static function account(string $username, string $email, array $attributes): User
    {
        $user = User::query()->where('username', $username)->orWhere('email', $email)->orderByRaw('username = ? DESC', [$username])->first() ?? new User;
        $user->fill($attributes + ['username' => $username, 'email' => $email, 'password' => 'password', 'is_active' => true])->save();

        return $user;
    }
}
