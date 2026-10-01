<?php

namespace Tests\Feature;

use App\Models\FamilyCard;
use App\Models\User;
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
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.full_name', 'Budi Santoso');
        $this->assertDatabaseHas('citizens', [
            'nik_hash' => hash('sha256', '9876543210987654'),
            'version' => 1,
        ]);
    }

    public function test_admin_without_scope_cannot_create_citizen(): void
    {
        // Admin with only finance scope, no citizen.manage scope
        $adminWithoutScope = User::factory()->admin(['finance.manage'])->create();

        $familyCard = FamilyCard::factory()->create();

        $response = $this->actingAs($adminWithoutScope)->postJson('/api/v1/admin/citizens', [
            'family_card_id' => $familyCard->id,
            'nik' => '9876543210987654',
            'full_name' => 'Budi Santoso',
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
}
