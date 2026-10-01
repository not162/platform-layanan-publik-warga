<?php

namespace Tests\Feature;

use App\Models\Citizen;
use App\Models\FamilyCard;
use App\Models\Letter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LetterModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_citizen_can_create_letter(): void
    {
        $user = User::factory()->warga()->create();
        $familyCard = FamilyCard::factory()->create();
        $citizen = Citizen::factory()->create(['user_id' => $user->id, 'family_card_id' => $familyCard->id]);

        $response = $this->actingAs($user)->postJson('/api/v1/letters', [
            'type' => 'domicile',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.status', 'draft');

        $this->assertDatabaseHas('letters', [
            'citizen_id' => $citizen->id,
            'type' => 'domicile',
            'status' => 'draft',
        ]);

        // Audit log test
        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'Letter',
            'action' => 'create',
        ]);
    }

    public function test_letter_status_workflow_with_scoped_permissions(): void
    {
        $citizenUser = User::factory()->warga()->create();
        $familyCard = FamilyCard::factory()->create();
        $citizen = Citizen::factory()->create(['user_id' => $citizenUser->id, 'family_card_id' => $familyCard->id]);

        $verifierAdmin = User::factory()->admin(['letter.verify'])->create();
        $approverAdmin = User::factory()->admin(['letter.approve'])->create();
        $unscopedAdmin = User::factory()->admin(['finance.manage'])->create();

        $letter = Letter::create([
            'citizen_id' => $citizen->id,
            'type' => 'business',
            'status' => 'draft',
            'version' => 1,
        ]);

        // 1. Citizen submits letter (draft -> submitted)
        $response = $this->actingAs($citizenUser)->patchJson('/api/v1/letters/'.$letter->id.'/status', [
            'status' => 'submitted',
            'version' => 1,
        ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('letters', ['id' => $letter->id, 'status' => 'submitted', 'version' => 2]);

        // 2. Citizen cannot approve (submitted -> approved)
        $response = $this->actingAs($citizenUser)->patchJson('/api/v1/letters/'.$letter->id.'/status', [
            'status' => 'approved',
            'version' => 2,
        ]);
        $response->assertStatus(409);

        // 3. Unscoped admin cannot verify (lacks letter.verify scope)
        $response = $this->actingAs($unscopedAdmin)->patchJson('/api/v1/letters/'.$letter->id.'/status', [
            'status' => 'verified',
            'version' => 2,
        ]);
        $response->assertStatus(403);

        // 4. Verifier Admin verifies letter (submitted -> verified)
        $response = $this->actingAs($verifierAdmin)->patchJson('/api/v1/letters/'.$letter->id.'/status', [
            'status' => 'verified',
            'version' => 2,
        ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('letters', ['id' => $letter->id, 'status' => 'verified', 'version' => 3]);

        // 5. Approver Admin approves letter (verified -> approved)
        $response = $this->actingAs($approverAdmin)->patchJson('/api/v1/letters/'.$letter->id.'/status', [
            'status' => 'approved',
            'version' => 3,
            'letter_number' => 'SURAT/123/2026',
        ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('letters', ['id' => $letter->id, 'status' => 'approved', 'letter_number' => 'SURAT/123/2026', 'version' => 4]);

        // 6. Optimistic Locking Test: Re-updating using old version 3 returns 409 Conflict
        $response = $this->actingAs($approverAdmin)->patchJson('/api/v1/letters/'.$letter->id.'/status', [
            'status' => 'approved',
            'version' => 3,
        ]);
        $response->assertStatus(409);
    }
}
