<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('jenis_surat', function (Blueprint $table) {
            $table->string('template_key', 100)->nullable()->after('kode_surat');
            $table->unsignedInteger('template_version')->default(1)->after('template_key');
            $table->json('form_schema')->nullable()->after('syarat_dokumen');
            $table->json('approval_flow')->nullable()->after('form_schema');
            $table->unsignedInteger('estimated_process_hours')->nullable()->default(24)->after('approval_flow');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jenis_surat', function (Blueprint $table) {
            $table->dropColumn([
                'template_key',
                'template_version',
                'form_schema',
                'approval_flow',
                'estimated_process_hours',
            ]);
        });
    }
};
