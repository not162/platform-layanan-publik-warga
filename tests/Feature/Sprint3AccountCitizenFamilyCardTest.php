<?php

namespace Tests\Feature;

use App\Models\Citizen;
use App\Models\FamilyCard;
use App\Models\Letter;
use App\Models\User;
use App\Services\CitizenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class Sprint3AccountCitizenFamilyCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_authentication_and_role_access_boundaries_block_unauthorized_users(): void
    {
        $warga = User::factory()->warga()->create();
        $admin = User::factory()->admin(['citizen.read', 'citizen.manage'])->create();
        $superadmin = User::factory()->superadmin()->create();

        // 1. Warga is blocked from admin citizens index (403 Forbidden)
        $wargaResponse = $this->actingAs($warga)->getJson('/api/v1/admin/citizens');
        $wargaResponse->assertStatus(Response::HTTP_FORBIDDEN);

        // 2. Warga is blocked from family cards administration (403 Forbidden)
        $wargaKkResponse = $this->actingAs($warga)->getJson('/api/v1/admin/family-cards');
        $wargaKkResponse->assertStatus(Response::HTTP_FORBIDDEN);

        // 3. Admin with citizen.read / citizen.manage permissions can access
        $adminResponse = $this->actingAs($admin)->getJson('/api/v1/admin/citizens');
        $adminResponse->assertStatus(Response::HTTP_OK);

        $adminKkResponse = $this->actingAs($admin)->getJson('/api/v1/admin/family-cards');
        $adminKkResponse->assertStatus(Response::HTTP_OK);

        // 4. Superadmin has automatic bypass
        $superResponse = $this->actingAs($superadmin)->getJson('/api/v1/admin/citizens');
        $superResponse->assertStatus(Response::HTTP_OK);
    }

    public function test_citizen_user_single_source_of_truth_and_reconciliation(): void
    {
        $user = User::factory()->warga()->create(['id' => 77]);
        $familyCard = FamilyCard::factory()->create();

        // Citizen has a distinct ID (e.g. 150) but links to user_id = 77
        $citizen = Citizen::create([
            'id' => 150,
            'family_card_id' => $familyCard->id,
            'user_id' => $user->id,
            'nik' => '3201123456789099',
            'nik_hash' => hash('sha256', '3201123456789099'),
            'name' => 'Warga Rekonsiliasi S3',
            'full_name' => 'Warga Rekonsiliasi S3',
            'gender' => 'Laki-laki',
            'place_of_birth' => 'Jakarta',
            'date_of_birth' => '1990-05-15',
            'religion' => 'Islam',
            'status' => 'tetap',
            'status_warga' => 'tetap',
            'is_active' => true,
            'version' => 1,
        ]);

        // User::citizen() must resolve via citizens.user_id = users.id
        $this->assertNotNull($user->citizen);
        $this->assertEquals($citizen->id, $user->citizen->id);
        $this->assertEquals($user->id, $user->citizen->user_id);
        $this->assertEquals('Warga Rekonsiliasi S3', $user->citizen->name);

        // Reverse relation Citizen::user() resolves to User
        $this->assertNotNull($citizen->user);
        $this->assertEquals(77, $citizen->user->id);

        // Attempting to register another user with the exact same NIK hash throws error
        $this->postJson('/api/v1/register', [
            'nik' => '3201123456789099',
            'name' => 'Duplikat Warga',
            'email' => 'duplikat@example.com',
            'password' => 'Password@12345',
            'password_confirmation' => 'Password@12345',
        ])->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_family_card_optimistic_locking_prevents_concurrent_overwrite(): void
    {
        $admin = User::factory()->admin(['citizen.manage'])->create();
        $familyCard = FamilyCard::create([
            'no_kk' => '3201998877665544',
            'no_kk_hash' => hash('sha256', '3201998877665544'),
            'address' => 'Jl. Kebon Kosong No. 12',
            'rt' => '04',
            'rw' => '09',
            'version' => 1,
        ]);

        // 1. Initial update with correct matching version = 1
        $updateResponse = $this->actingAs($admin)->patchJson("/api/v1/admin/family-cards/{$familyCard->id}", [
            'address' => 'Jl. Kebon Kosong No. 12B (Revisi Pertama)',
            'version' => 1,
        ]);

        $updateResponse->assertStatus(Response::HTTP_OK);
        $familyCard->refresh();
        $this->assertEquals(2, $familyCard->version);
        $this->assertEquals('Jl. Kebon Kosong No. 12B (Revisi Pertama)', $familyCard->address);

        // 2. Concurrent/stale update attempting to submit version 1 again
        $staleResponse = $this->actingAs($admin)->patchJson("/api/v1/admin/family-cards/{$familyCard->id}", [
            'address' => 'Jl. Kebon Kosong No. 12C (Tabrakan Data)',
            'version' => 1,
        ]);

        $staleResponse->assertStatus(Response::HTTP_CONFLICT);
        $familyCard->refresh();
        $this->assertEquals(2, $familyCard->version);
        $this->assertEquals('Jl. Kebon Kosong No. 12B (Revisi Pertama)', $familyCard->address);
    }

    public function test_citizen_verification_and_rejection_workflow_by_admin(): void
    {
        $admin = User::factory()->admin(['citizen.manage'])->create();
        $wargaUser = User::factory()->warga()->create();
        $familyCard = FamilyCard::factory()->create();

        $pendingCitizen = Citizen::create([
            'family_card_id' => $familyCard->id,
            'user_id' => $wargaUser->id,
            'nik' => '3201776655443322',
            'nik_hash' => hash('sha256', '3201776655443322'),
            'name' => 'Warga Pending S3',
            'full_name' => 'Warga Pending S3',
            'gender' => 'Perempuan',
            'place_of_birth' => 'Bandung',
            'date_of_birth' => '1995-08-20',
            'religion' => 'Islam',
            'status' => 'pending_verification',
            'status_warga' => 'pending_verification',
            'is_active' => true,
            'version' => 1,
        ]);

        // 1. Admin verifies citizen
        $verifyResponse = $this->actingAs($admin)->postJson("/api/v1/admin/citizens/{$pendingCitizen->id}/verify");
        $verifyResponse->assertStatus(Response::HTTP_OK);

        $pendingCitizen->refresh();
        $this->assertEquals('tetap', $pendingCitizen->status_warga);
        $this->assertStringContainsString('Telah diverifikasi', $pendingCitizen->verification_notes);

        // 2. Create another pending citizen and reject with reason
        $anotherWargaUser = User::factory()->warga()->create();
        $rejectCitizen = Citizen::create([
            'family_card_id' => $familyCard->id,
            'user_id' => $anotherWargaUser->id,
            'nik' => '3201554433221100',
            'nik_hash' => hash('sha256', '3201554433221100'),
            'name' => 'Warga Ditolak S3',
            'full_name' => 'Warga Ditolak S3',
            'gender' => 'Laki-laki',
            'place_of_birth' => 'Surabaya',
            'date_of_birth' => '1992-11-10',
            'religion' => 'Islam',
            'status' => 'pending_verification',
            'status_warga' => 'pending_verification',
            'is_active' => true,
            'version' => 1,
        ]);

        $rejectResponse = $this->actingAs($admin)->postJson("/api/v1/admin/citizens/{$rejectCitizen->id}/reject", [
            'reason' => 'Berkas KTP tidak jelas dan tidak berdomisili di RT 04.',
        ]);
        $rejectResponse->assertStatus(Response::HTTP_OK);

        $rejectCitizen->refresh();
        $this->assertEquals('ditolak', $rejectCitizen->status_warga);
        $this->assertStringContainsString('Berkas KTP tidak jelas', $rejectCitizen->verification_notes);
    }

    public function test_sensitive_identity_data_protection_and_hash_lookup(): void
    {
        $rawNik = '3201112233445566';
        $wargaUser = User::factory()->warga()->create();
        $familyCard = FamilyCard::factory()->create();

        $citizen = Citizen::create([
            'family_card_id' => $familyCard->id,
            'user_id' => $wargaUser->id,
            'nik' => $rawNik,
            'nik_hash' => hash('sha256', $rawNik),
            'name' => 'Warga Privasi PDP',
            'full_name' => 'Warga Privasi PDP',
            'gender' => 'Laki-laki',
            'place_of_birth' => 'Jakarta',
            'date_of_birth' => '1988-03-25',
            'religion' => 'Islam',
            'address' => 'Jl. Kebon Kosong RT 04 RW 09',
            'status' => 'tetap',
            'status_warga' => 'tetap',
            'is_active' => true,
            'version' => 1,
        ]);

        // 1. Raw database value for NIK is encrypted (not equal to rawNik)
        $rawDbRecord = DB::table('citizens')->where('id', $citizen->id)->first();
        $this->assertNotEquals($rawNik, $rawDbRecord->nik);

        // 2. Exact lookup using SHA-256 blind index hash succeeds
        $service = app(CitizenService::class);
        $foundCitizen = $service->findByNik($rawNik);
        $this->assertNotNull($foundCitizen);
        $this->assertEquals($citizen->id, $foundCitizen->id);

        // 3. API profile endpoint (/api/v1/me) does NOT expose plaintext NIK
        $meResponse = $this->actingAs($wargaUser)->getJson('/api/v1/me');
        $meResponse->assertStatus(Response::HTTP_OK);
        $meContent = $meResponse->getContent();
        $this->assertStringNotContainsString($rawNik, $meContent);
        $this->assertStringContainsString('3201********5566', $meContent);

        // 4. Export profile endpoint (/api/v1/me/export) masks NIK
        $exportResponse = $this->actingAs($wargaUser)->getJson('/api/v1/me/export');
        $exportResponse->assertStatus(Response::HTTP_OK);
        $exportContent = $exportResponse->getContent();
        $this->assertStringNotContainsString($rawNik, $exportContent);
    }

    public function test_citizen_deletion_safety_protection(): void
    {
        $service = app(CitizenService::class);
        $wargaUser = User::factory()->warga()->create();
        $familyCard = FamilyCard::factory()->create();

        $citizen = Citizen::create([
            'family_card_id' => $familyCard->id,
            'user_id' => $wargaUser->id,
            'nik' => '3201443322110099',
            'nik_hash' => hash('sha256', '3201443322110099'),
            'name' => 'Warga Dilindungi Hapus',
            'full_name' => 'Warga Dilindungi Hapus',
            'gender' => 'Laki-laki',
            'place_of_birth' => 'Jakarta',
            'date_of_birth' => '1990-01-01',
            'religion' => 'Islam',
            'status' => 'tetap',
            'status_warga' => 'tetap',
            'is_active' => true,
            'version' => 1,
        ]);

        // Attach an active letter to citizen
        Letter::create([
            'citizen_id' => $citizen->id,
            'type' => 'Surat Pengantar',
            'ticket_number' => 'SRT-S3-DELETE-PROTECT',
            'status' => 'submitted',
            'version' => 1,
        ]);

        // Attempting to delete citizen with historical letters must abort with HTTP 422
        $this->expectException(HttpException::class);
        $service->delete($citizen);
    }
}
