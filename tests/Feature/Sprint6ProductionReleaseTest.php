<?php

namespace Tests\Feature;

use App\Models\FinanceTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class Sprint6ProductionReleaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_health_check_endpoint_returns_up_status(): void
    {
        $response = $this->get('/up');
        $response->assertStatus(Response::HTTP_OK);
    }

    public function test_public_transparency_and_service_catalog_endpoints(): void
    {
        // 1. Service types catalog
        $servicesResponse = $this->getJson('/api/v1/public/services');
        $servicesResponse->assertStatus(Response::HTTP_OK);
        $this->assertArrayHasKey('data', $servicesResponse->json());

        // 2. Public financial summary
        $financeResponse = $this->getJson('/api/v1/finance/summary');
        $financeResponse->assertStatus(Response::HTTP_OK);
        $this->assertArrayHasKey('total_income_idr', $financeResponse->json('data'));
        $this->assertArrayHasKey('net_balance_idr', $financeResponse->json('data'));

        // 3. Ensure no sensitive credentials leaked
        $rawContent = $servicesResponse->getContent();
        $this->assertStringNotContainsString('password', $rawContent);
        $this->assertStringNotContainsString('remember_token', $rawContent);
    }

    public function test_cryptographic_backup_store_creation_and_integrity_checksum(): void
    {
        Storage::fake('local');

        $bendahara = User::factory()->admin([
            'finance.report',
            'finance.manage',
            'finance.read',
        ])->create(['name' => 'Bendahara Backup S6']);

        // Seed sample published finance transaction
        FinanceTransaction::create([
            'transaction_number' => 'TX-S6-001',
            'type' => 'income',
            'source' => 'other',
            'category' => 'Kas Awal RT',
            'amount' => 1000000,
            'amount_idr' => 1000000,
            'description' => 'Saldo awal kas lingkungan',
            'transaction_date' => now()->toDateString(),
            'status' => 'published',
            'version' => 1,
            'created_by' => $bendahara->id,
        ]);

        $backupResponse = $this->actingAs($bendahara)->postJson('/api/v1/admin/finance/backup-store');
        $backupResponse->assertStatus(Response::HTTP_CREATED);
        $backupResponse->assertJsonStructure([
            'message',
            'data' => [
                'backup_file',
                'checksum_sha256',
                'total_records',
                'created_at',
            ],
        ]);

        $filePath = $backupResponse->json('data.backup_file');
        $checksum = $backupResponse->json('data.checksum_sha256');

        Storage::disk('local')->assertExists($filePath);
        $storedContent = Storage::disk('local')->get($filePath);
        $computedChecksum = hash('sha256', $storedContent);

        $this->assertEquals($checksum, $computedChecksum, 'File backup hash must match the recorded SHA-256 checksum exactly.');

        // Verify list backups returns the file
        $listResponse = $this->actingAs($bendahara)->getJson('/api/v1/admin/finance/backups');
        $listResponse->assertStatus(Response::HTTP_OK);
        $this->assertNotEmpty($listResponse->json('data'));
    }

    public function test_unauthorized_warga_blocked_from_backup_and_administrative_operations(): void
    {
        $wargaUser = User::factory()->warga()->create();

        // Attempting to create backup store -> 401/403
        $createBackup = $this->actingAs($wargaUser)->postJson('/api/v1/admin/finance/backup-store');
        $this->assertContains($createBackup->status(), [Response::HTTP_FORBIDDEN, Response::HTTP_UNAUTHORIZED]);

        // Attempting to list backups -> 401/403
        $listBackups = $this->actingAs($wargaUser)->getJson('/api/v1/admin/finance/backups');
        $this->assertContains($listBackups->status(), [Response::HTTP_FORBIDDEN, Response::HTTP_UNAUTHORIZED]);
    }
}
