<?php

namespace Tests\Feature;

use App\Events\ComplaintStatusUpdated;
use App\Events\DuePaymentRecorded;
use App\Events\LetterStatusUpdated;
use App\Models\Announcement;
use App\Models\Citizen;
use App\Models\Complaint;
use App\Models\FamilyCard;
use App\Models\Letter;
use App\Models\LetterType;
use App\Models\ResidentDue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class Sprint5ApiRealtimeUatTest extends TestCase
{
    use RefreshDatabase;

    public function test_warga_dashboard_summary_returns_personalized_metrics_and_envelope(): void
    {
        $wargaUser = User::factory()->warga()->create();
        $familyCard = FamilyCard::factory()->create();
        $citizen = Citizen::create([
            'family_card_id' => $familyCard->id,
            'user_id' => $wargaUser->id,
            'nik' => '3201554433221100',
            'nik_hash' => hash('sha256', '3201554433221100'),
            'name' => 'Warga Dashboard S5',
            'full_name' => 'Warga Dashboard S5',
            'gender' => 'Laki-laki',
            'place_of_birth' => 'Bandung',
            'date_of_birth' => '1995-05-15',
            'status_warga' => 'tetap',
            'is_active' => true,
            'version' => 1,
        ]);

        // Create 1 draft letter, 1 submitted letter
        Letter::create([
            'citizen_id' => $citizen->id,
            'type' => 'Surat Pengantar',
            'ticket_number' => 'SRT-DSH-001',
            'status' => 'draft',
            'version' => 1,
        ]);
        Letter::create([
            'citizen_id' => $citizen->id,
            'type' => 'Surat Keterangan Domisili',
            'ticket_number' => 'SRT-DSH-002',
            'status' => 'submitted',
            'version' => 1,
        ]);

        // Create 1 active complaint
        Complaint::create([
            'user_id' => $wargaUser->id,
            'citizen_id' => $citizen->id,
            'ticket_number' => 'ADU-DSH-001',
            'title' => 'Lampu PJU Padam',
            'description' => 'Lampu jalan mati di tiang nomor 5.',
            'status' => 'submitted',
            'version' => 1,
        ]);

        // Create 1 unpaid due
        ResidentDue::create([
            'citizen_id' => $citizen->id,
            'period_year' => 2026,
            'period_month' => 10,
            'amount_due_idr' => 50000,
            'due_date' => '2026-10-25',
            'status' => 'UNPAID',
        ]);

        $response = $this->actingAs($wargaUser)->getJson('/api/v1/dashboard/summary');

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonStructure([
            'data' => [
                'role',
                'letters' => ['total', 'draft', 'submitted', 'verified', 'approved', 'completed', 'rejected'],
                'complaints' => ['total', 'submitted', 'in_progress', 'resolved'],
                'dues' => ['total_billed_idr', 'total_paid_idr', 'total_outstanding_idr', 'unpaid_count', 'paid_count'],
                'finance' => ['net_balance_idr', 'total_income_idr', 'total_expense_idr'],
            ],
            'meta' => ['request_id', 'role', 'version', 'updated_at'],
            'message',
        ]);

        $this->assertEquals('WARGA', $response->json('data.role'));
        $this->assertEquals(2, $response->json('data.letters.total'));
        $this->assertEquals(1, $response->json('data.letters.draft'));
        $this->assertEquals(1, $response->json('data.letters.submitted'));
        $this->assertEquals(1, $response->json('data.complaints.total'));
        $this->assertEquals(1, $response->json('data.dues.unpaid_count'));
    }

    public function test_admin_dashboard_summary_returns_executive_oversight_metrics(): void
    {
        $admin = User::factory()->admin(['letter.verify', 'letter.approve', 'complaint.manage', 'security.manage'])->create();

        // Pending verification letter (status = submitted)
        $citizen = Citizen::factory()->create();
        Letter::create([
            'citizen_id' => $citizen->id,
            'type' => 'Surat Pengantar SKCK',
            'ticket_number' => 'SRT-ADM-001',
            'status' => 'submitted',
            'version' => 1,
        ]);

        // Pending approval letter (status = verified)
        Letter::create([
            'citizen_id' => $citizen->id,
            'type' => 'Surat Domisili Usaha',
            'ticket_number' => 'SRT-ADM-002',
            'status' => 'verified',
            'version' => 1,
        ]);

        // Open complaint
        Complaint::create([
            'ticket_number' => 'ADU-ADM-001',
            'title' => 'Saluran Air Mampet',
            'description' => 'Sampah daun menyumbat got RT 04.',
            'status' => 'submitted',
            'version' => 1,
        ]);

        $response = $this->actingAs($admin)->getJson('/api/v1/dashboard/summary');

        $response->assertStatus(Response::HTTP_OK);
        $this->assertNotEquals('WARGA', $response->json('data.role'));
        $this->assertEquals(1, $response->json('data.letters.pending_verification'));
        $this->assertEquals(1, $response->json('data.letters.pending_approval'));
        $this->assertGreaterThanOrEqual(1, $response->json('data.complaints.open'));
        $this->assertGreaterThanOrEqual(1, $response->json('data.citizens.total'));
    }

    public function test_incremental_polling_sync_endpoint_returns_changed_records(): void
    {
        $wargaUser = User::factory()->warga()->create();
        $citizen = Citizen::factory()->create(['user_id' => $wargaUser->id]);

        $pastTime = now()->subMinutes(30);

        // Record updated 10 minutes ago
        $letter = Letter::create([
            'citizen_id' => $citizen->id,
            'type' => 'Surat Pengantar KTP',
            'ticket_number' => 'SRT-SYNC-001',
            'status' => 'submitted',
            'version' => 1,
        ]);

        // Published announcement
        Announcement::create([
            'title' => 'Gotong Royong Bersih Selokan',
            'content' => 'Kerja bakti hari Minggu pagi.',
            'category' => 'kegiatan',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $syncResponse = $this->actingAs($wargaUser)->getJson('/api/v1/sync?since='.$pastTime->toIso8601String());

        $syncResponse->assertStatus(Response::HTTP_OK);
        $syncResponse->assertJsonStructure([
            'data' => [
                'letters',
                'complaints',
                'announcements',
                'dues',
            ],
            'meta' => [
                'server_time',
                'since',
                'has_changes',
                'changes_count',
                'next_cursor',
            ],
            'message',
        ]);

        $this->assertTrue($syncResponse->json('meta.has_changes'));
        $this->assertGreaterThanOrEqual(1, $syncResponse->json('meta.changes_count'));
        $this->assertNotEmpty($syncResponse->json('data.letters'));
        $this->assertNotEmpty($syncResponse->json('data.announcements'));
    }

    public function test_etag_and_304_not_modified_polling_behavior(): void
    {
        $wargaUser = User::factory()->warga()->create();

        // First request: receives 200 with ETag header
        $firstResponse = $this->actingAs($wargaUser)->getJson('/api/v1/sync');
        $firstResponse->assertStatus(Response::HTTP_OK);

        $etag = $firstResponse->headers->get('ETag');
        $this->assertNotEmpty($etag, 'Response must include ETag header');

        // Second request with If-None-Match matching the ETag: receives 304 Not Modified
        $secondResponse = $this->actingAs($wargaUser)->withHeaders([
            'If-None-Match' => $etag,
        ])->getJson('/api/v1/sync');

        $secondResponse->assertStatus(Response::HTTP_NOT_MODIFIED);
        $this->assertEmpty($secondResponse->getContent(), '304 Not Modified response body must be empty');
    }

    public function test_realtime_broadcast_events_dispatched_on_core_mutations(): void
    {
        Event::fake([
            LetterStatusUpdated::class,
            ComplaintStatusUpdated::class,
            DuePaymentRecorded::class,
        ]);

        $wargaUser = User::factory()->warga()->create();
        $citizen = Citizen::factory()->create(['user_id' => $wargaUser->id]);
        $admin = User::factory()->admin([
            'letter.verify',
            'letter.approve',
            'complaint.manage',
            'finance.payment.manage',
            'finance.payment.create',
            'finance.manage',
        ])->create();

        $letterType = LetterType::firstOrCreate(
            ['kode_surat' => 'SKD-S5'],
            ['nama_surat' => 'Surat Domisili', 'is_active' => true]
        );

        // 1. Submit letter -> dispatches LetterStatusUpdated
        $createResponse = $this->actingAs($wargaUser)->postJson('/api/v1/letters', [
            'jenis_surat_id' => $letterType->id,
            'type' => 'Surat Domisili',
            'keperluan' => 'Pendaftaran Sekolah',
        ]);
        $letterId = $createResponse->json('data.id');

        $this->actingAs($wargaUser)->postJson("/api/v1/letters/{$letterId}/submit", [
            'version' => 1,
        ]);

        Event::assertDispatched(LetterStatusUpdated::class, function ($event) use ($letterId) {
            return $event->letter->id === $letterId && $event->status === 'submitted';
        });

        // 2. Complaint status update -> dispatches ComplaintStatusUpdated
        $complaint = Complaint::create([
            'user_id' => $wargaUser->id,
            'citizen_id' => $citizen->id,
            'ticket_number' => 'ADU-EVT-001',
            'title' => 'Pohon Tumbang',
            'description' => 'Pohon tumbang menghalangi jalan utama.',
            'status' => 'submitted',
            'version' => 1,
        ]);

        $this->actingAs($admin)->patchJson("/api/v1/admin/complaints/{$complaint->id}/status", [
            'status' => 'in_progress',
            'admin_response' => 'Tim penanganan darurat telah diterjunkan ke lokasi.',
            'version' => 1,
        ]);

        Event::assertDispatched(ComplaintStatusUpdated::class, function ($event) use ($complaint) {
            return $event->complaint->id === $complaint->id && $event->status === 'in_progress';
        });

        // 3. Due payment recording -> dispatches DuePaymentRecorded
        $due = ResidentDue::create([
            'citizen_id' => $citizen->id,
            'period_year' => 2026,
            'period_month' => 10,
            'amount_due_idr' => 40000,
            'due_date' => '2026-10-25',
            'status' => 'UNPAID',
        ]);

        $this->actingAs($admin)->postJson('/api/v1/admin/finance/payments', [
            'resident_due_id' => $due->id,
            'amount_paid_idr' => 40000,
            'payment_method' => 'cash',
        ]);

        Event::assertDispatched(DuePaymentRecorded::class, function ($event) use ($due) {
            return $event->due->id === $due->id;
        });
    }

    public function test_uat_end_to_end_cross_role_workflow_integration(): void
    {
        // Actors
        $wargaUser = User::factory()->warga()->create(['name' => 'Warga UAT Pelapor']);
        $citizen = Citizen::factory()->create([
            'user_id' => $wargaUser->id,
            'full_name' => 'Warga UAT Pelapor',
        ]);

        $sekretaris = User::factory()->admin(['letter.verify', 'letter.read'])->create(['name' => 'Sekretaris RT UAT']);
        $ketuaRt = User::factory()->admin(['letter.approve', 'letter.complete', 'letter.read', 'finance.report'])->create(['name' => 'Ketua RT UAT']);
        $bendahara = User::factory()->admin(['finance.payment.manage', 'finance.payment.create', 'finance.manage', 'finance.read'])->create(['name' => 'Bendahara RT UAT']);

        $letterType = LetterType::firstOrCreate(
            ['kode_surat' => 'SKU'],
            ['nama_surat' => 'Surat Keterangan Usaha', 'is_active' => true]
        );

        // Step 1: Warga creates and submits letter
        $letterRes = $this->actingAs($wargaUser)->postJson('/api/v1/letters', [
            'jenis_surat_id' => $letterType->id,
            'type' => 'Surat Keterangan Usaha',
            'keperluan' => 'Pengajuan KUR Mikro Bank',
        ]);
        $letterRes->assertStatus(Response::HTTP_CREATED);
        $letterId = $letterRes->json('data.id');

        $this->actingAs($wargaUser)->postJson("/api/v1/letters/{$letterId}/submit", ['version' => 1])
            ->assertStatus(Response::HTTP_OK);

        // Step 2: Sekretaris verifies letter
        $sekretarisDashboard = $this->actingAs($sekretaris)->getJson('/api/v1/dashboard/summary');
        $sekretarisDashboard->assertStatus(Response::HTTP_OK);
        $this->assertGreaterThanOrEqual(1, $sekretarisDashboard->json('data.letters.pending_verification'));

        $this->actingAs($sekretaris)->postJson("/api/v1/admin/letters/{$letterId}/verify", [
            'version' => 2,
            'notes' => 'Persyaratan izin usaha dan KTP terlampir lengkap.',
        ])->assertStatus(Response::HTTP_OK);

        // Step 3: Ketua RT approves and completes letter
        $this->actingAs($ketuaRt)->postJson("/api/v1/admin/letters/{$letterId}/approve", [
            'version' => 3,
            'letter_number' => 'SKU/099/RT04/10/2026',
        ])->assertStatus(Response::HTTP_OK);

        $this->actingAs($ketuaRt)->postJson("/api/v1/admin/letters/{$letterId}/complete", [
            'version' => 4,
        ])->assertStatus(Response::HTTP_OK);

        // Step 4: Warga checks dashboard and syncs -> confirms letter completed
        $wargaSync = $this->actingAs($wargaUser)->getJson('/api/v1/sync?since='.now()->subHour()->toIso8601String());
        $wargaSync->assertStatus(Response::HTTP_OK);
        $completedLetters = collect($wargaSync->json('data.letters'))->where('id', $letterId);
        $this->assertEquals('completed', $completedLetters->first()['status']);

        // Step 5: Due payment & ledger integration
        $due = ResidentDue::create([
            'citizen_id' => $citizen->id,
            'period_year' => 2026,
            'period_month' => 10,
            'amount_due_idr' => 25000,
            'due_date' => '2026-10-25',
            'status' => 'UNPAID',
        ]);

        $this->actingAs($bendahara)->postJson('/api/v1/admin/finance/payments', [
            'resident_due_id' => $due->id,
            'amount_paid_idr' => 25000,
            'payment_method' => 'cash',
            'notes' => 'Pelunasan iuran kebersihan UAT',
        ])->assertStatus(Response::HTTP_CREATED);

        $due->refresh();
        $this->assertEquals('PAID', $due->status);
    }
}
