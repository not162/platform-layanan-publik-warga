<?php

namespace Tests\Feature;

use App\Models\Citizen;
use App\Models\Complaint;
use App\Models\Letter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class Sprint2LetterComplaintSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_letter_cannot_be_reverted_to_draft_or_modified(): void
    {
        $superadmin = User::factory()->superadmin()->create();
        $warga = User::factory()->warga()->create();
        $citizen = Citizen::factory()->create(['user_id' => $warga->id]);

        $letter = Letter::create([
            'citizen_id' => $citizen->id,
            'type' => 'Surat Pengantar',
            'ticket_number' => 'SRT-S2-COMPLETED',
            'status' => 'completed',
            'letter_number' => 'SK/099/RT01/10/2026',
            'version' => 5,
        ]);

        // Attempting to revert completed letter to draft
        $revertResponse = $this->actingAs($superadmin)->patchJson("/api/v1/letters/{$letter->id}", [
            'status' => 'draft',
            'version' => 5,
        ]);

        $revertResponse->assertStatus(Response::HTTP_CONFLICT);
        $this->assertDatabaseHas('letters', [
            'id' => $letter->id,
            'status' => 'completed',
        ]);

        // Attempting to alter status of completed letter to verified
        $alterResponse = $this->actingAs($superadmin)->patchJson("/api/v1/letters/{$letter->id}", [
            'status' => 'verified',
            'version' => 5,
        ]);
        $alterResponse->assertStatus(Response::HTTP_CONFLICT);
    }

    public function test_letter_cannot_be_reverted_back_to_draft_from_submitted(): void
    {
        $admin = User::factory()->admin(['letter.verify'])->create();
        $warga = User::factory()->warga()->create();
        $citizen = Citizen::factory()->create(['user_id' => $warga->id]);

        $letter = Letter::create([
            'citizen_id' => $citizen->id,
            'type' => 'Surat Pengantar',
            'ticket_number' => 'SRT-S2-SUBMITTED',
            'status' => 'submitted',
            'version' => 2,
        ]);

        $response = $this->actingAs($admin)->patchJson("/api/v1/letters/{$letter->id}", [
            'status' => 'draft',
            'version' => 2,
        ]);

        $response->assertStatus(Response::HTTP_CONFLICT);
        $this->assertDatabaseHas('letters', [
            'id' => $letter->id,
            'status' => 'submitted',
        ]);
    }

    public function test_warga_cannot_view_or_access_other_citizens_complaints(): void
    {
        $wargaA = User::factory()->warga()->create();
        $wargaB = User::factory()->warga()->create();

        $complaintA = Complaint::create([
            'ticket_number' => 'ADU-A-001',
            'user_id' => $wargaA->id,
            'title' => 'Keluhan Pribadi Warga A',
            'description' => 'Ada gangguan di depan rumah.',
            'status' => 'submitted',
            'version' => 1,
        ]);

        // Warga B tries to view Warga A's complaint
        $response = $this->actingAs($wargaB)->getJson("/api/v1/complaints/{$complaintA->id}");
        $response->assertStatus(Response::HTTP_FORBIDDEN);

        // Warga A can view their own complaint
        $ownResponse = $this->actingAs($wargaA)->getJson("/api/v1/complaints/{$complaintA->id}");
        $ownResponse->assertOk()
            ->assertJsonPath('data.title', 'Keluhan Pribadi Warga A');
    }

    public function test_anonymous_complaint_protects_identity_in_all_views(): void
    {
        $warga = User::factory()->warga()->create([
            'name' => 'Warga Pelapor Anonim',
            'email' => 'anonim@example.com',
        ]);
        $admin = User::factory()->admin(['complaint.manage'])->create();

        $complaint = Complaint::create([
            'ticket_number' => 'ADU-ANON-001',
            'user_id' => $warga->id,
            'title' => 'Laporan Pungli di Lapangan',
            'description' => 'Diduga ada tarikan retri liar di area parkir pos RT.',
            'is_anonymous' => true,
            'status' => 'submitted',
            'version' => 1,
        ]);

        // 1. Warga viewing own complaint sees null user identity
        $wargaResponse = $this->actingAs($warga)->getJson("/api/v1/complaints/{$complaint->id}");
        $wargaResponse->assertOk()
            ->assertJsonPath('data.is_anonymous', true)
            ->assertJsonPath('data.user', null);

        // 2. Admin viewing complaint in admin detail endpoint also sees null user
        $adminShow = $this->actingAs($admin)->getJson("/api/v1/complaints/{$complaint->id}");
        $adminShow->assertOk()
            ->assertJsonPath('data.is_anonymous', true)
            ->assertJsonPath('data.user', null);

        // 3. Admin listing complaints has user as null for anonymous records
        $adminList = $this->actingAs($admin)->getJson('/api/v1/admin/complaints');
        $adminList->assertOk();
        $records = $adminList->json('data');
        $found = collect($records)->firstWhere('ticket_number', 'ADU-ANON-001');
        $this->assertNotNull($found);
        $this->assertTrue($found['is_anonymous']);
        $this->assertNull($found['user']);
    }

    public function test_public_endpoints_do_not_leak_raw_nik_or_passwords(): void
    {
        $publicSummary = $this->getJson('/api/v1/finance/summary');
        $publicSummary->assertOk();
        $this->assertStringNotContainsString('password', $publicSummary->getContent());
        $this->assertStringNotContainsString('remember_token', $publicSummary->getContent());

        $letterCatalog = $this->getJson('/api/v1/letter-types');
        $letterCatalog->assertOk();
        $this->assertStringNotContainsString('password', $letterCatalog->getContent());
    }
}
