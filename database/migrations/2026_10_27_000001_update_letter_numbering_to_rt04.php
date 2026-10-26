<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Format penomoran surat memakai RT 04 (sebelumnya RT01 dari template awal).
     */
    public function up(): void
    {
        DB::table('jenis_surat')->where('format_penomoran', 'like', '%/RT01/%')->get()->each(function ($row) {
            DB::table('jenis_surat')->where('id', $row->id)->update(['format_penomoran' => str_replace('/RT01/', '/RT04/', $row->format_penomoran)]);
        });
    }

    public function down(): void
    {
        DB::table('jenis_surat')->where('format_penomoran', 'like', '%/RT04/%')->get()->each(function ($row) {
            DB::table('jenis_surat')->where('id', $row->id)->update(['format_penomoran' => str_replace('/RT04/', '/RT01/', $row->format_penomoran)]);
        });
    }
};
