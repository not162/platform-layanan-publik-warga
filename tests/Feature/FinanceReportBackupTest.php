<?php

namespace Tests\Feature;

use App\Models\Citizen;
use App\Models\FinanceTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FinanceReportBackupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_bendahara_can_download_monthly_report_and_citizen_dues(): void
    {
        $bendahara = User::factory()->bendahara()->create();

        // Seed sample finance transaction
        FinanceTransaction::query()->create([
            'created_by' => $bendahara->id,
            'category' => 'Iuran Warga Bulanan',
            'type' => 'income',
            'amount' => 250000,
            'description' => 'Iuran Warga Budi Santoso',
            'transaction_date' => now(),
            'status' => 'published',
            'version' => 1,
        ]);

        $citizen = Citizen::factory()->create(['full_name' => 'Budi Santoso']);

        $resMonthly = $this->actingAs($bendahara)->get('/api/v1/admin/finance/reports/monthly?year='.now()->year.'&month='.now()->month.'&format=html');
        $resMonthly->assertStatus(200);
        $resMonthly->assertHeader('Content-Disposition');
        $this->assertStringContainsString('Laporan Keuangan Kas RT', $resMonthly->getContent());

        $resDues = $this->actingAs($bendahara)->get('/api/v1/admin/finance/reports/citizen-dues?year='.now()->year.'&month='.now()->month);
        $resDues->assertStatus(200);
        $this->assertStringContainsString('REKAPITULASI PEMBAYARAN KAS', $resDues->getContent());
        $this->assertStringContainsString('Budi Santoso', $resDues->getContent());
    }

    public function test_sekretaris_can_download_financial_reports(): void
    {
        $sekretaris = User::factory()->sekretaris()->create();

        $resMonthly = $this->actingAs($sekretaris)->get('/api/v1/admin/finance/reports/monthly?year='.now()->year.'&month='.now()->month.'&format=csv');
        $resMonthly->assertStatus(200);
        $this->assertStringContainsString('ID,Tanggal,Kategori,Tipe,Jumlah,Keterangan', $resMonthly->getContent());
    }

    public function test_bendahara_and_sekretaris_can_create_and_list_backup_store(): void
    {
        $bendahara = User::factory()->bendahara()->create();

        FinanceTransaction::query()->create([
            'created_by' => $bendahara->id,
            'category' => 'Pemeliharaan Lampu Jalan',
            'type' => 'expense',
            'amount' => 150000,
            'description' => 'Beli bohlam LED 20 watt',
            'transaction_date' => now(),
            'status' => 'published',
            'version' => 1,
        ]);

        $resBackup = $this->actingAs($bendahara)->postJson('/api/v1/admin/finance/backups');
        $resBackup->assertStatus(201);
        $resBackup->assertJsonStructure([
            'message',
            'data' => [
                'backup_file',
                'checksum_sha256',
                'total_records',
                'created_at',
            ],
        ]);

        $sha256 = $resBackup->json('data.checksum_sha256');
        $this->assertNotEmpty($sha256);
        $this->assertEquals(64, strlen($sha256));

        $resList = $this->actingAs($bendahara)->getJson('/api/v1/admin/finance/backups');
        $resList->assertStatus(200);
        $this->assertCount(1, $resList->json('data'));
    }

    public function test_warga_cannot_access_finance_reports_or_backup_store(): void
    {
        $warga = User::factory()->warga()->create();

        $resMonthly = $this->actingAs($warga)->get('/api/v1/admin/finance/reports/monthly');
        $resMonthly->assertStatus(403);

        $resDues = $this->actingAs($warga)->get('/api/v1/admin/finance/reports/citizen-dues');
        $resDues->assertStatus(403);

        $resBackup = $this->actingAs($warga)->postJson('/api/v1/admin/finance/backups');
        $resBackup->assertStatus(403);
    }
}
