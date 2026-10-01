<?php

namespace Tests\Feature;

use App\Models\Citizen;
use App\Models\FamilyCard;
use App\Models\Letter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CitizenServiceRoleMatrixTest extends TestCase
{
    use RefreshDatabase;

    public function test_warga_cannot_verify_or_approve_letter(): void
    {
        $warga = User::factory()->warga()->create();
        $familyCard = FamilyCard::factory()->create();
        $citizen = Citizen::factory()->create(['user_id' => $warga->id, 'family_card_id' => $familyCard->id]);

        $letter = Letter::create([
            'citizen_id' => $citizen->id,
            'type' => 'domicile',
            'ticket_number' => 'SRT-MATRIX-01',
            'status' => 'submitted',
            'version' => 1,
        ]);

        // Warga cannot verify
        $response = $this->actingAs($warga)->postJson("/api/v1/admin/letters/{$letter->id}/verify", [
            'version' => 1,
        ]);
        $response->assertStatus(403);

        // Warga cannot approve
        $response = $this->actingAs($warga)->postJson("/api/v1/admin/letters/{$letter->id}/approve", [
            'version' => 1,
        ]);
        $response->assertStatus(403);
    }

    public function test_sekretaris_can_verify_but_cannot_approve_letter(): void
    {
        $sekretaris = User::factory()->sekretaris()->create();
        $citizen = Citizen::factory()->create();

        $letter = Letter::create([
            'citizen_id' => $citizen->id,
            'type' => 'domicile',
            'ticket_number' => 'SRT-MATRIX-02',
            'status' => 'submitted',
            'version' => 1,
        ]);

        // Sekretaris verifies -> OK
        $response = $this->actingAs($sekretaris)->postJson("/api/v1/admin/letters/{$letter->id}/verify", [
            'version' => 1,
            'notes' => 'Berkas lengkap diverifikasi oleh sekretaris',
        ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('letters', ['id' => $letter->id, 'status' => 'verified', 'version' => 2]);

        // Sekretaris attempts to approve -> 403 Forbidden (requires letter.approve)
        $response = $this->actingAs($sekretaris)->postJson("/api/v1/admin/letters/{$letter->id}/approve", [
            'version' => 2,
        ]);
        $response->assertStatus(403);
    }

    public function test_sekretaris_cannot_modify_finance_without_explicit_permission(): void
    {
        $sekretaris = User::factory()->sekretaris()->create();

        $response = $this->actingAs($sekretaris)->postJson('/api/v1/admin/finance', [
            'type' => 'income',
            'amount' => 500000,
            'category' => 'iuran_warga',
            'description' => 'Iuran warga ilegal oleh sekretaris',
            'transaction_date' => now()->toDateString(),
        ]);

        $response->assertStatus(403);
    }

    public function test_ketua_rt_can_approve_verified_letter(): void
    {
        $ketuaRt = User::factory()->ketuaRt()->create();
        $citizen = Citizen::factory()->create();

        $letter = Letter::create([
            'citizen_id' => $citizen->id,
            'type' => 'domicile',
            'ticket_number' => 'SRT-MATRIX-03',
            'status' => 'verified',
            'version' => 2,
        ]);

        $response = $this->actingAs($ketuaRt)->postJson("/api/v1/admin/letters/{$letter->id}/approve", [
            'version' => 2,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('letters', [
            'id' => $letter->id,
            'status' => 'approved',
            'approved_by' => $ketuaRt->id,
            'version' => 3,
        ]);
    }

    public function test_ketua_rt_cannot_automatically_manage_finance(): void
    {
        $ketuaRt = User::factory()->ketuaRt()->create();

        $response = $this->actingAs($ketuaRt)->postJson('/api/v1/admin/finance', [
            'type' => 'income',
            'amount' => 1000000,
            'category' => 'iuran_warga',
            'description' => 'Pengeluaran oleh RT tanpa bendahara',
            'transaction_date' => now()->toDateString(),
        ]);

        $response->assertStatus(403);
    }

    public function test_bendahara_can_manage_finance_but_cannot_verify_letters(): void
    {
        $bendahara = User::factory()->bendahara()->create();
        $citizen = Citizen::factory()->create();

        $letter = Letter::create([
            'citizen_id' => $citizen->id,
            'type' => 'domicile',
            'ticket_number' => 'SRT-MATRIX-04',
            'status' => 'submitted',
            'version' => 1,
        ]);

        // Bendahara cannot verify
        $response = $this->actingAs($bendahara)->postJson("/api/v1/admin/letters/{$letter->id}/verify", [
            'version' => 1,
        ]);
        $response->assertStatus(403);

        // Bendahara can record finance
        $financeResponse = $this->actingAs($bendahara)->postJson('/api/v1/admin/finance', [
            'type' => 'income',
            'amount' => 250000,
            'category' => 'iuran_sampah',
            'description' => 'Pembayaran iuran sampah bulanan',
            'transaction_date' => now()->toDateString(),
        ]);
        $financeResponse->assertStatus(201);
    }

    public function test_petugas_keamanan_can_manage_security_but_cannot_approve_letters(): void
    {
        $securityOfficer = User::factory()->petugasKeamanan()->create();
        $citizen = Citizen::factory()->create();

        $letter = Letter::create([
            'citizen_id' => $citizen->id,
            'type' => 'domicile',
            'ticket_number' => 'SRT-MATRIX-05',
            'status' => 'verified',
            'version' => 2,
        ]);

        // Security officer cannot approve letters
        $response = $this->actingAs($securityOfficer)->postJson("/api/v1/admin/letters/{$letter->id}/approve", [
            'version' => 2,
        ]);
        $response->assertStatus(403);

        // Security officer can access security reports
        $securityResponse = $this->actingAs($securityOfficer)->getJson('/api/v1/admin/security-reports');
        $securityResponse->assertStatus(200);
    }

    public function test_superadmin_can_access_all_administrative_resources(): void
    {
        $superadmin = User::factory()->superadmin()->create();
        $citizen = Citizen::factory()->create();

        $letter = Letter::create([
            'citizen_id' => $citizen->id,
            'type' => 'domicile',
            'ticket_number' => 'SRT-MATRIX-06',
            'status' => 'submitted',
            'version' => 1,
        ]);

        // Superadmin verifies
        $response = $this->actingAs($superadmin)->postJson("/api/v1/admin/letters/{$letter->id}/verify", [
            'version' => 1,
        ]);
        $response->assertStatus(200);

        // Superadmin approves
        $response = $this->actingAs($superadmin)->postJson("/api/v1/admin/letters/{$letter->id}/approve", [
            'version' => 2,
        ]);
        $response->assertStatus(200);

        // Superadmin accesses finance
        $financeResponse = $this->actingAs($superadmin)->getJson('/api/v1/admin/finance');
        $financeResponse->assertStatus(200);
    }

    public function test_bendahara_cannot_access_sensitive_citizen_letters(): void
    {
        $bendahara = User::factory()->bendahara()->create();

        // Bendahara cannot view administrative letters list
        $response = $this->actingAs($bendahara)->getJson('/api/v1/admin/letters');
        $response->assertStatus(403);
    }

    public function test_ketua_rt_cannot_automatically_change_system_permissions(): void
    {
        $ketuaRt = User::factory()->ketuaRt()->create();

        $this->assertFalse($ketuaRt->hasPermission('roles.manage'));
        $this->assertFalse($ketuaRt->hasPermission('permissions.manage'));
    }
}
