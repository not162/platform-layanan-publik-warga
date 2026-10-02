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
        Schema::create('financial_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('quarter'); // 1, 2, 3, 4
            $table->unsignedInteger('revision')->default(1);
            $table->date('period_start');
            $table->date('period_end');
            $table->bigInteger('opening_balance_idr')->default(0);
            $table->bigInteger('income_idr')->default(0);
            $table->bigInteger('expense_idr')->default(0);
            $table->bigInteger('closing_balance_idr')->default(0);
            $table->bigInteger('dues_assessed_idr')->default(0);
            $table->bigInteger('dues_collected_idr')->default(0);
            $table->bigInteger('dues_outstanding_idr')->default(0);
            $table->string('status', 30)->default('draft'); // draft, published, archived
            $table->dateTime('generated_at')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('checksum_sha256', 64)->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->unique(['year', 'quarter', 'revision'], 'financial_reports_year_quarter_revision_unique');
            $table->index(['year', 'quarter', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_reports');
    }
};
