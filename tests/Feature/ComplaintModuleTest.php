<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplaintModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_warga_can_submit_complaint_with_auto_generated_ticket(): void
    {
        $warga = User::factory()->warga()->create();

        $response = $this->actingAs($warga)->postJson('/api/v1/complaints', [
            'title' => 'Lampu Jalan Mati di RT 01',
            'description' => 'Lampu jalan tiang nomor 3 sudah 2 hari mati, membahayakan warga saat malam.',
            'kategori' => 'fasilitas',
            'lokasi' => 'Depan rumah nomor 12',
            'priority' => 'sedang',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.title', 'Lampu Jalan Mati di RT 01');
        $response->assertJsonPath('data.status', 'submitted');
        $response->assertJsonPath('data.kategori', 'fasilitas');

        $ticket = $response->json('data.ticket_number');
        $this->assertStringStartsWith('ADU-', $ticket);

        $this->assertDatabaseHas('complaints', [
            'ticket_number' => $ticket,
            'user_id' => $warga->id,
            'status' => 'submitted',
            'is_anonymous' => 0,
        ]);

        // Audit log created
        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'Complaint',
            'action' => 'complaint.submitted',
        ]);
    }

    public function test_verified_warga_can_submit_anonymous_complaint(): void
    {
        $warga = User::factory()->warga()->create();

        $response = $this->actingAs($warga)->postJson('/api/v1/complaints', [
            'title' => 'Saluran Air Mampet',
            'description' => 'Tumpukan sampah menyumbat selokan di gang buntu.',
            'is_anonymous' => true,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.is_anonymous', true);
        $response->assertJsonPath('data.user', null);

        $ticket = $response->json('data.ticket_number');

        $this->assertDatabaseHas('complaints', [
            'ticket_number' => $ticket,
            'user_id' => $warga->id,
            'is_anonymous' => 1,
        ]);
    }

    public function test_guest_cannot_submit_complaint(): void
    {
        $response = $this->postJson('/api/v1/complaints', [
            'title' => 'Laporan Orang Luar',
            'description' => 'Pengaduan dari pengguna tanpa otentikasi NIK warga.',
        ]);

        $response->assertStatus(401);
    }

    public function test_admin_with_scope_can_review_and_resolve_complaint(): void
    {
        $admin = User::factory()->admin(['complaint.manage'])->create();
        $warga = User::factory()->warga()->create();

        $complaint = Complaint::create([
            'ticket_number' => 'ADU-202610-001',
            'user_id' => $warga->id,
            'title' => 'Kebisingan Malam Hari',
            'description' => 'Ada aktivitas bengkel larut malam.',
            'status' => 'submitted',
            'version' => 1,
        ]);

        // 1. Admin reviews complaint
        $response = $this->actingAs($admin)->patchJson('/api/v1/admin/complaints/'.$complaint->id.'/status', [
            'status' => 'reviewed',
            'version' => 1,
            'admin_response' => 'Laporan diterima dan akan dikoordinasikan dengan petugas keamanan.',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'reviewed');
        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'status' => 'reviewed',
            'assigned_admin_id' => $admin->id,
            'version' => 2,
        ]);

        // 2. Admin resolves complaint
        $response = $this->actingAs($admin)->patchJson('/api/v1/admin/complaints/'.$complaint->id.'/status', [
            'status' => 'resolved',
            'version' => 2,
            'admin_response' => 'Telah ditindaklanjuti. Pemilik bengkel bersedia tutup pukul 21:00 WIB.',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'resolved');
        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'status' => 'resolved',
            'version' => 3,
        ]);
    }

    public function test_admin_without_scope_cannot_update_complaint(): void
    {
        $adminWithoutScope = User::factory()->admin(['finance.manage'])->create();
        $complaint = Complaint::create([
            'ticket_number' => 'ADU-202610-002',
            'title' => 'Test',
            'description' => 'Desc',
            'status' => 'submitted',
            'version' => 1,
        ]);

        $response = $this->actingAs($adminWithoutScope)->patchJson('/api/v1/admin/complaints/'.$complaint->id.'/status', [
            'status' => 'reviewed',
            'version' => 1,
        ]);

        $response->assertStatus(403);
    }

    public function test_warga_cannot_update_complaint_status(): void
    {
        $warga = User::factory()->warga()->create();
        $complaint = Complaint::create([
            'ticket_number' => 'ADU-202610-003',
            'user_id' => $warga->id,
            'title' => 'Test',
            'description' => 'Desc',
            'status' => 'submitted',
            'version' => 1,
        ]);

        $response = $this->actingAs($warga)->patchJson('/api/v1/admin/complaints/'.$complaint->id.'/status', [
            'status' => 'resolved',
            'version' => 1,
        ]);

        $response->assertStatus(403);
    }

    public function test_optimistic_locking_prevents_stale_complaint_updates(): void
    {
        $admin = User::factory()->admin(['complaint.manage'])->create();
        $complaint = Complaint::create([
            'ticket_number' => 'ADU-202610-004',
            'title' => 'Test',
            'description' => 'Desc',
            'status' => 'submitted',
            'version' => 2,
        ]);

        // Attempting to update with stale version 1
        $response = $this->actingAs($admin)->patchJson('/api/v1/admin/complaints/'.$complaint->id.'/status', [
            'status' => 'reviewed',
            'version' => 1,
        ]);

        $response->assertStatus(409);
    }

    public function test_unauthorized_warga_cannot_view_another_citizens_complaint(): void
    {
        $wargaA = User::factory()->warga()->create();
        $wargaB = User::factory()->warga()->create();

        $complaint = Complaint::create([
            'ticket_number' => 'ADU-202610-005',
            'user_id' => $wargaA->id,
            'title' => 'Keluhan Pribadi',
            'description' => 'Isi keluhan.',
            'status' => 'submitted',
            'version' => 1,
        ]);

        // Warga B tries to view complaint of Warga A
        $response = $this->actingAs($wargaB)->getJson('/api/v1/complaints/'.$complaint->id);
        $response->assertStatus(403);

        // Warga A can view own complaint
        $response = $this->actingAs($wargaA)->getJson('/api/v1/complaints/'.$complaint->id);
        $response->assertStatus(200);
    }
}
