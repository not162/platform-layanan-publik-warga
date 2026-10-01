<?php

namespace Tests\Feature;

use App\Models\Citizen;
use App\Models\FamilyCard;
use App\Models\User;
use App\Services\CitizenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CitizenModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_with_scope_can_create_citizen(): void
    {
        $admin = User::factory()->admin(['citizen.manage'])->create();

        $familyCard = FamilyCard::factory()->create([
            'no_kk' => '1234567890123456',
            'no_kk_hash' => hash('sha256', '1234567890123456'),
        ]);

        $response = $this->actingAs($admin)->postJson('/api/v1/admin/citizens', [
            'family_card_id' => $familyCard->id,
            'nik' => '9876543210987654',
            'full_name' => 'Budi Santoso',
            'gender' => 'Laki-laki',
            'place_of_birth' => 'Jakarta',
            'date_of_birth' => '1990-05-15',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.full_name', 'Budi Santoso');
        $this->assertDatabaseHas('citizens', [
            'nik_hash' => hash('sha256', '9876543210987654'),
            'version' => 1,
        ]);

        // Audit log created
        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'Citizen',
            'action' => 'create',
        ]);
    }

    public function test_admin_without_scope_cannot_create_citizen(): void
    {
        $adminWithoutScope = User::factory()->admin(['finance.manage'])->create();
        $familyCard = FamilyCard::factory()->create();

        $response = $this->actingAs($adminWithoutScope)->postJson('/api/v1/admin/citizens', [
            'family_card_id' => $familyCard->id,
            'nik' => '9876543210987654',
            'full_name' => 'Budi Santoso',
            'gender' => 'Laki-laki',
            'place_of_birth' => 'Jakarta',
            'date_of_birth' => '1990-05-15',
        ]);

        $response->assertStatus(403);
    }

    public function test_warga_cannot_create_citizen(): void
    {
        $warga = User::factory()->warga()->create();
        $familyCard = FamilyCard::factory()->create();

        $response = $this->actingAs($warga)->postJson('/api/v1/admin/citizens', [
            'family_card_id' => $familyCard->id,
            'nik' => '9876543210987654',
            'full_name' => 'Budi Santoso',
            'gender' => 'Laki-laki',
            'place_of_birth' => 'Jakarta',
            'date_of_birth' => '1990-05-15',
        ]);

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_citizen(): void
    {
        $response = $this->postJson('/api/v1/admin/citizens', [
            'nik' => '123',
        ]);
        $response->assertStatus(401);
    }

    public function test_warga_can_view_own_profile_with_masked_nik(): void
    {
        $user = User::factory()->warga()->create();
        $familyCard = FamilyCard::factory()->create();
        $citizen = Citizen::factory()->create([
            'user_id' => $user->id,
            'family_card_id' => $familyCard->id,
            'nik' => '3174012345678901',
            'nik_hash' => hash('sha256', '3174012345678901'),
            'full_name' => 'Siti Rahmawati',
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/me');

        $response->assertStatus(200);
        $response->assertJsonPath('data.citizen.full_name', 'Siti Rahmawati');
        // Masked NIK check: 3174********8901
        $response->assertJsonPath('data.citizen.nik_masked', '3174********8901');
        // Raw NIK must NEVER be returned in JSON response
        $this->assertStringNotContainsString('3174012345678901', $response->getContent());
    }

    public function test_warga_can_update_allowed_profile_fields(): void
    {
        $user = User::factory()->warga()->create();
        $familyCard = FamilyCard::factory()->create();
        $citizen = Citizen::factory()->create([
            'user_id' => $user->id,
            'family_card_id' => $familyCard->id,
            'phone' => '081200000000',
            'occupation' => 'Pedagang',
        ]);

        $response = $this->actingAs($user)->patchJson('/api/v1/me', [
            'phone' => '081299998888',
            'occupation' => 'Wiraswasta',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.citizen.phone', '081299998888');
        $response->assertJsonPath('data.citizen.occupation', 'Wiraswasta');

        $this->assertDatabaseHas('citizens', [
            'id' => $citizen->id,
            'phone' => '081299998888',
            'occupation' => 'Wiraswasta',
        ]);
    }

    public function test_optimistic_locking_prevents_stale_citizen_updates(): void
    {
        $admin = User::factory()->admin(['citizen.manage'])->create();
        $familyCard = FamilyCard::factory()->create();
        $citizen = Citizen::factory()->create([
            'family_card_id' => $familyCard->id,
            'version' => 2,
        ]);

        // Attempting to update with an obsolete version (1 instead of 2)
        $response = $this->actingAs($admin)->putJson('/api/v1/admin/citizens/'.$citizen->id, [
            'full_name' => 'Nama Baru',
            'version' => 1,
        ]);

        $response->assertStatus(409);
    }

    public function test_hash_based_search_in_citizen_service(): void
    {
        $service = app(CitizenService::class);
        $familyCard = FamilyCard::factory()->create([
            'no_kk' => '1122334455667788',
            'no_kk_hash' => hash('sha256', '1122334455667788'),
        ]);
        $citizen = Citizen::factory()->create([
            'family_card_id' => $familyCard->id,
            'nik' => '9988776655443322',
            'nik_hash' => hash('sha256', '9988776655443322'),
        ]);

        // Exact match search by NIK
        $foundCitizen = $service->findByNik('9988776655443322');
        $this->assertNotNull($foundCitizen);
        $this->assertEquals($citizen->id, $foundCitizen->id);

        // Search by invalid NIK returns null
        $this->assertNull($service->findByNik('0000000000000000'));

        // Exact match search by No KK
        $foundKk = $service->findByNoKk('1122334455667788');
        $this->assertNotNull($foundKk);
        $this->assertEquals($familyCard->id, $foundKk->id);
    }
}
