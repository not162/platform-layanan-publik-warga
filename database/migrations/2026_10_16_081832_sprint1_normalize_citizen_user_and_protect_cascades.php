<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Data Reconciliation: sinkronkan citizens.user_id dari users.warga_id jika belum terisi
        $usersWithWarga = DB::table('users')->whereNotNull('warga_id')->get();
        foreach ($usersWithWarga as $user) {
            $citizen = DB::table('citizens')->where('id', $user->warga_id)->first();
            if ($citizen && is_null($citizen->user_id)) {
                // Pastikan tidak ada akun lain yang sudah menggunakan user_id ini
                $existing = DB::table('citizens')->where('user_id', $user->id)->first();
                if (! $existing) {
                    DB::table('citizens')->where('id', $citizen->id)->update([
                        'user_id' => $user->id,
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        // 2. Pastikan relasi unique pada citizens.user_id jika belum ada
        Schema::table('citizens', function (Blueprint $table) {
            // family_card_id tetap nullable untuk mendukung pendaftaran mandiri warga baru
            if (Schema::hasColumn('citizens', 'family_card_id')) {
                $table->unsignedBigInteger('family_card_id')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reversible without data loss
    }
};
