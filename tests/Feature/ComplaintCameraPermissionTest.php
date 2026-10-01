<?php

namespace Tests\Feature;

use App\Models\Citizen;
use App\Models\Complaint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ComplaintCameraPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_verified_warga_can_upload_complaint_with_location_details_and_camera_image(): void
    {
        $wargaUser = User::factory()->warga()->create();
        $citizen = Citizen::factory()->create(['user_id' => $wargaUser->id, 'full_name' => 'Budi Santoso']);

        $image = UploadedFile::fake()->image('gangguan_jalan.jpg', 600, 600);

        $response = $this->actingAs($wargaUser)->post('/api/v1/complaints', [
            'title' => 'Lampu Penerangan Mati di Tikungan',
            'category' => 'infrastruktur',
            'street_name' => 'Jl. Kenanga Raya',
            'location_detail' => 'Depan tiang listrik nomor 3 dekat pos ronda',
            'description' => 'Lampu mati sejak dua malam lalu membahayakan warga saat melintas malam hari.',
            'image' => $image,
        ], ['Accept' => 'application/json']);

        $response->assertStatus(201);
        $this->assertDatabaseHas('complaints', [
            'title' => 'Lampu Penerangan Mati di Tikungan',
            'user_id' => $wargaUser->id,
        ]);

        $createdComplaint = Complaint::query()->first();
        $this->assertStringContainsString('Jl. Kenanga Raya', $createdComplaint->lokasi);
        $this->assertStringContainsString('Depan tiang listrik nomor 3', $createdComplaint->lokasi);
        $this->assertNotNull($createdComplaint->attachment_path);
    }

    public function test_guest_cannot_upload_camera_image_for_complaint(): void
    {
        $image = UploadedFile::fake()->image('unauthorized_photo.jpg', 400, 400);

        $response = $this->postJson('/api/v1/complaints', [
            'title' => 'Laporan dari Tamu',
            'category' => 'lingkungan',
            'street_name' => 'Jl. Mawar',
            'location_detail' => 'Sebelah gapura',
            'description' => 'Mencoba mengunggah foto tanpa login akun warga.',
            'image' => $image,
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Akses upload gambar/kamera hanya diizinkan untuk akun warga yang terverifikasi.',
        ]);
    }

    public function test_guest_can_still_submit_text_only_complaint(): void
    {
        $response = $this->postJson('/api/v1/complaints', [
            'title' => 'Laporan Teks Anonim',
            'category' => 'ketertiban',
            'street_name' => 'Jl. Flamboyan',
            'location_detail' => 'Dekat taman bermain',
            'description' => 'Ada sampah menumpuk di pinggir jalan.',
            'is_anonymous' => true,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('complaints', [
            'title' => 'Laporan Teks Anonim',
            'is_anonymous' => true,
            'user_id' => null,
            'attachment_path' => null,
        ]);
    }
}
