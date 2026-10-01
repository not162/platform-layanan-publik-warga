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

        // Audit log created
        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'Letter',
            'action' => 'letter.submitted',
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
            'ticket_number' => 'SRT-202610-0001',
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
        $response = $this->actingAs($unscopedAdmin)->patchJson('/api/v1/admin/letters/'.$letter->id.'/verify', [
            'version' => 2,
        ]);
        $response->assertStatus(403);

        // 4. Verifier Admin verifies letter (submitted -> verified)
        $response = $this->actingAs($verifierAdmin)->patchJson('/api/v1/admin/letters/'.$letter->id.'/verify', [
            'version' => 2,
            'notes' => 'Dokumen lengkap dan valid',
        ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('letters', [
            'id' => $letter->id,
            'status' => 'verified',
            'verified_by' => $verifierAdmin->id,
            'version' => 3,
        ]);

        // 5. Approver Admin approves letter (verified -> approved)
        $response = $this->actingAs($approverAdmin)->patchJson('/api/v1/admin/letters/'.$letter->id.'/approve', [
            'version' => 3,
            'letter_number' => 'SK/0001/RT01/10/2026',
        ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('letters', [
            'id' => $letter->id,
            'status' => 'approved',
            'approved_by' => $approverAdmin->id,
            'letter_number' => 'SK/0001/RT01/10/2026',
            'version' => 4,
        ]);

        // 6. Optimistic Locking Test: Re-updating using old version 3 returns 409 Conflict
        $response = $this->actingAs($approverAdmin)->patchJson('/api/v1/admin/letters/'.$letter->id.'/approve', [
            'version' => 3,
        ]);
        $response->assertStatus(409);
    }

    public function test_admin_can_reject_letter_with_mandatory_reason(): void
    {
        $citizenUser = User::factory()->warga()->create();
        $familyCard = FamilyCard::factory()->create();
        $citizen = Citizen::factory()->create(['user_id' => $citizenUser->id, 'family_card_id' => $familyCard->id]);

        $verifierAdmin = User::factory()->admin(['letter.verify'])->create();

        $letter = Letter::create([
            'citizen_id' => $citizen->id,
            'type' => 'residence',
            'status' => 'submitted',
            'ticket_number' => 'SRT-202610-0002',
            'version' => 1,
        ]);

        // Rejection without reason fails validation with 422
        $response = $this->actingAs($verifierAdmin)->patchJson('/api/v1/admin/letters/'.$letter->id.'/reject', [
            'version' => 1,
            'rejection_reason' => '',
        ]);
        $response->assertStatus(422);

        // Rejection with reason succeeds
        $response = $this->actingAs($verifierAdmin)->patchJson('/api/v1/admin/letters/'.$letter->id.'/reject', [
            'version' => 1,
            'rejection_reason' => 'KTP lampiran buram dan tidak terbaca',
        ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('letters', [
            'id' => $letter->id,
            'status' => 'rejected',
            'rejection_reason' => 'KTP lampiran buram dan tidak terbaca',
            'version' => 2,
        ]);
    }

    public function test_public_can_verify_approved_letter_by_token(): void
    {
        $citizen = Citizen::factory()->create(['full_name' => 'Ahmad Dahlan']);
        $letter = Letter::create([
            'citizen_id' => $citizen->id,
            'type' => 'domicile',
            'status' => 'approved',
            'ticket_number' => 'SRT-202610-0003',
            'letter_number' => 'SK/0010/RT01/10/2026',
            'verification_token' => 'token-valid-12345678',
            'approved_at' => now(),
            'version' => 2,
        ]);

        // Public endpoint without auth
        $response = $this->getJson('/api/v1/public/letter/verify/token-valid-12345678');
        $response->assertStatus(200);
        $response->assertJsonPath('valid', true);
        $response->assertJsonPath('data.letter_number', 'SK/0010/RT01/10/2026');
        $response->assertJsonPath('data.recipient_name', 'Ahmad Dahlan');

        // Non-existent token returns 404
        $notFound = $this->getJson('/api/v1/public/letter/verify/invalid-token-xyz');
        $notFound->assertStatus(404);
        $notFound->assertJsonPath('valid', false);
    }

    public function test_public_can_track_letter_by_ticket(): void
    {
        $citizen = Citizen::factory()->create();
        $letter = Letter::create([
            'citizen_id' => $citizen->id,
            'type' => 'domicile',
            'status' => 'verified',
            'ticket_number' => 'SRT-202610-9999',
            'version' => 2,
        ]);

        $response = $this->getJson('/api/v1/public/track/SRT-202610-9999');
        $response->assertStatus(200);
        $response->assertJsonPath('found', true);
        $response->assertJsonPath('data.status', 'verified');

        $notFound = $this->getJson('/api/v1/public/track/SRT-NONEXISTENT');
        $notFound->assertStatus(404);
        $notFound->assertJsonPath('found', false);
    }

    public function test_unauthorized_warga_cannot_view_another_citizens_letter(): void
    {
        $wargaA = User::factory()->warga()->create();
        $citizenA = Citizen::factory()->create(['user_id' => $wargaA->id]);

        $wargaB = User::factory()->warga()->create();
        $citizenB = Citizen::factory()->create(['user_id' => $wargaB->id]);

        $letterA = Letter::create([
            'citizen_id' => $citizenA->id,
            'type' => 'domicile',
            'ticket_number' => 'SRT-A-1',
            'status' => 'draft',
            'version' => 1,
        ]);

        // Warga B tries to view letter A
        $response = $this->actingAs($wargaB)->getJson('/api/v1/letters/'.$letterA->id);
        $response->assertStatus(403);

        // Warga A can view own letter
        $response = $this->actingAs($wargaA)->getJson('/api/v1/letters/'.$letterA->id);
        $response->assertStatus(200);
    }
}
