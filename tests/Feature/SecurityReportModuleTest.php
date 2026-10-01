<?php

namespace Tests\Feature;

use App\Models\Citizen;
use App\Models\SecurityReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityReportModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_citizen_can_create_security_report(): void
    {
        $wargaUser = User::factory()->warga()->create();
        $citizen = Citizen::factory()->create(['user_id' => $wargaUser->id, 'full_name' => 'Warga Pelapor']);

        $response = $this->actingAs($wargaUser)->postJson('/api/v1/security-reports', [
            'category' => 'kehilangan',
            'severity' => 'medium',
            'title' => 'Kehilangan Sepeda di Gang Mawar',
            'description' => 'Sepeda lipat warna merah hilang saat diparkir di depan teras rumah semalam.',
            'location' => 'Jl. Mawar No. 12 RT 01',
            'incident_at' => now()->subHours(8)->toDateTimeString(),
            'is_anonymous' => false,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.status', 'submitted');
        $response->assertJsonPath('data.severity', 'medium');
        $response->assertJsonPath('data.reporter', 'Warga Pelapor');

        $this->assertDatabaseHas('security_reports', [
            'reporter_user_id' => $wargaUser->id,
            'category' => 'kehilangan',
            'status' => 'submitted',
        ]);
    }

    public function test_emergency_severity_includes_official_emergency_contacts(): void
    {
        $wargaUser = User::factory()->warga()->create();
        Citizen::factory()->create(['user_id' => $wargaUser->id]);

        $response = $this->actingAs($wargaUser)->postJson('/api/v1/security-reports', [
            'category' => 'kebakaran',
            'severity' => 'emergency',
            'title' => 'Asap Tebal Terlihat di Belakang Gudang',
            'description' => 'Terlihat percikan dan asap membubung tinggi di gudang kosong blok barat.',
            'location' => 'Gudang Blok Barat RT 01',
            'incident_at' => now()->toDateTimeString(),
            'is_anonymous' => false,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.emergency_notice.contacts.polisi', '110');
        $response->assertJsonPath('data.emergency_notice.contacts.pemadam_kebakaran', '113');
        $response->assertJsonPath('data.emergency_notice.contacts.layanan_darurat_terpadu', '112');
    }

    public function test_security_officer_can_assign_and_resolve_security_report(): void
    {
        $officer = User::factory()->petugasKeamanan()->create();
        $warga = User::factory()->warga()->create();

        $report = SecurityReport::create([
            'reporter_user_id' => $warga->id,
            'ticket_number' => 'SEC-202610-001',
            'category' => 'keributan',
            'severity' => 'low',
            'title' => 'Suara Musik Keras Melebihi Pukul 23.00',
            'description' => 'Ada tetangga menyalakan sound system sangat bising hingga larut malam.',
            'location' => 'Rumah No 05 Gang Dahlia',
            'incident_at' => now()->subHours(2),
            'status' => 'submitted',
            'version' => 1,
        ]);

        // Security officer updates and resolves report
        $updateResponse = $this->actingAs($officer)->patchJson("/api/v1/admin/security-reports/{$report->id}", [
            'version' => 1,
            'assigned_to' => $officer->id,
            'status' => 'resolved',
            'resolution' => 'Petugas telah mendatangi lokasi dan pemilik rumah telah mematikan musik secara kooperatif.',
        ]);

        $updateResponse->assertStatus(200);
        $updateResponse->assertJsonPath('data.status', 'resolved');
        $updateResponse->assertJsonPath('data.resolution', 'Petugas telah mendatangi lokasi dan pemilik rumah telah mematikan musik secara kooperatif.');

        $this->assertDatabaseHas('security_reports', [
            'id' => $report->id,
            'status' => 'resolved',
            'assigned_to' => $officer->id,
            'version' => 3,
        ]);
    }

    public function test_can_download_printable_security_report_document(): void
    {
        $officer = User::factory()->petugasKeamanan()->create();
        $warga = User::factory()->warga()->create(['name' => 'Budi Santoso']);

        $report = SecurityReport::create([
            'reporter_user_id' => $warga->id,
            'ticket_number' => 'SEC-202610-PRINT',
            'category' => 'pencurian',
            'severity' => 'high',
            'title' => 'Pencurian Helm di Parkiran Masjid',
            'description' => 'Helm merk KYT hilang saat salat isya berjamaah.',
            'location' => 'Parkiran Masjid Baiturrahman RT 01',
            'incident_at' => now()->subHours(3),
            'status' => 'resolved',
            'assigned_to' => $officer->id,
            'resolution' => 'Rekaman CCTV telah diperiksa bersama warga.',
            'resolved_at' => now()->subHour(),
            'version' => 2,
        ]);

        $response = $this->actingAs($officer)->get("/api/v1/admin/security-reports/{$report->id}/document");
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/html; charset=UTF-8');
        $this->assertStringContainsString('BERITA ACARA LAPORAN KEAMANAN & KETERTIBAN', $response->getContent());
        $this->assertStringContainsString('SEC-202610-PRINT', $response->getContent());
    }
}
