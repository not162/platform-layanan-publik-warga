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
        Schema::table('citizens', function (Blueprint $table) {
            if (! Schema::hasColumn('citizens', 'ktp_file_path')) {
                $table->string('ktp_file_path', 255)->nullable()->after('email');
            }
            if (! Schema::hasColumn('citizens', 'verification_notes')) {
                $table->text('verification_notes')->nullable()->after('ktp_file_path');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('citizens', function (Blueprint $table) {
            if (Schema::hasColumn('citizens', 'ktp_file_path')) {
                $table->dropColumn('ktp_file_path');
            }
            if (Schema::hasColumn('citizens', 'verification_notes')) {
                $table->dropColumn('verification_notes');
            }
        });
    }
};
