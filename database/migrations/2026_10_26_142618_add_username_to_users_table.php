<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Username menjadi identitas login utama; email bersifat opsional.
     * Satu NIK hanya boleh ada satu kali dan satu nama hanya sekali per KK.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 40)->nullable()->unique()->after('name');
            $table->string('email', 190)->nullable()->change();
        });

        DB::table('users')->whereNull('username')->orderBy('id')->each(function ($user) {
            $base = strtolower(preg_replace('/[^a-z0-9_]/i', '', explode('@', (string) $user->email)[0])) ?: 'user';
            DB::table('users')->where('id', $user->id)->update(['username' => substr($base, 0, 30).$user->id]);
        });

        Schema::table('citizens', function (Blueprint $table) {
            $table->string('full_name_key', 150)->nullable()->after('full_name');
        });

        DB::table('citizens')->orderBy('id')->each(function ($citizen) {
            DB::table('citizens')->where('id', $citizen->id)->update([
                'full_name_key' => mb_strtolower(trim(preg_replace('/\s+/', ' ', $citizen->full_name))),
            ]);
        });

        Schema::table('citizens', function (Blueprint $table) {
            $table->unique(['family_card_id', 'full_name_key'], 'citizens_kk_name_unique');
        });
    }

    public function down(): void
    {
        Schema::table('citizens', function (Blueprint $table) {
            $table->dropUnique('citizens_kk_name_unique');
            $table->dropColumn('full_name_key');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
