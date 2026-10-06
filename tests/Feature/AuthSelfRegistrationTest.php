<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Citizen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuthSelfRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_warga_can_self_register_even_if_nik_not_yet_in_database(): void
    {
        Storage::fake('local');
        $file = UploadedFile::fake()->create('ktp_warga.jpg', 300, 'image/jpeg');

        $payload = [
            'nik' => '3171031605030002',
            'name' => 'SWAN DARU TIRTA SANDHIKA',
            'email' => 'obryanswan@gmail.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'ktp_file' => $file,
            'notes' => 'Warga baru RT 01 No 15',
        ];

        $response = $this->postJson('/api/v1/register', $payload);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'is_pending_verification',
            'access_token',
            'user' => ['id', 'name', 'email', 'role', 'citizen'],
        ]);
        $this->assertTrue($response->json('is_pending_verification'));

        $this->assertDatabaseHas('users', [
            'email' => 'obryanswan@gmail.com',
            'role' => UserRole::WARGA->value,
        ]);

        $user = User::where('email', 'obryanswan@gmail.com')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->warga_id);

        $this->assertDatabaseHas('citizens', [
            'id' => $user->warga_id,
            'user_id' => $user->id,
            'full_name' => 'SWAN DARU TIRTA SANDHIKA',
            'email' => 'obryanswan@gmail.com',
            'status_warga' => 'pending_verification',
        ]);
    }

    public function test_pre_registered_citizen_links_to_new_user_on_registration(): void
    {
        $nik = '3171032001950001';
        $citizen = Citizen::factory()->create([
            'nik' => $nik,
            'nik_hash' => hash('sha256', $nik),
            'full_name' => 'Warga Lama Terdata',
            'user_id' => null,
            'status_warga' => 'tetap',
        ]);

        $payload = [
            'nik' => $nik,
            'name' => 'Warga Lama Terdata',
            'email' => 'wargalama@gmail.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/v1/register', $payload);

        $response->assertStatus(201);
        $this->assertEquals($citizen->id, $response->json('user.citizen.id'));

        $citizen->refresh();
        $this->assertNotNull($citizen->user_id);
    }

    public function test_duplicate_nik_registration_is_rejected(): void
    {
        $nik = '3171032001950002';
        $existingUser = User::factory()->warga()->create();
        Citizen::factory()->create([
            'nik' => $nik,
            'nik_hash' => hash('sha256', $nik),
            'user_id' => $existingUser->id,
        ]);

        $payload = [
            'nik' => $nik,
            'name' => 'Orang Lain',
            'email' => 'oranglain@gmail.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/v1/register', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['nik']);
    }

    public function test_check_citizen_endpoint_detects_already_registered_nik_and_hides_button(): void
    {
        $nik = '3171032001950003';
        $user = User::factory()->warga()->create();
        Citizen::factory()->create([
            'nik' => $nik,
            'nik_hash' => hash('sha256', $nik),
            'full_name' => 'Warga Punya Akun',
            'user_id' => $user->id,
        ]);

        $response = $this->postJson('/api/v1/auth/check-citizen', [
            'nik' => $nik,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'ALREADY_REGISTERED');
        $response->assertJsonPath('has_account', true);
        $response->assertJsonPath('allow_new_application', false); // Button ajukan warga baru TIDAK MUNCUL
        $this->assertStringContainsString('SUDAH TERDAFTAR', $response->json('message'));
    }

    public function test_check_citizen_endpoint_detects_pre_registered_citizen_in_rt_master(): void
    {
        $nik = '3171032001950004';
        Citizen::factory()->create([
            'nik' => $nik,
            'nik_hash' => hash('sha256', $nik),
            'full_name' => 'Warga Belum Aktivasi',
            'user_id' => null,
            'status_warga' => 'tetap',
        ]);

        $response = $this->postJson('/api/v1/auth/check-citizen', [
            'nik' => $nik,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'PRE_REGISTERED_RT');
        $response->assertJsonPath('has_account', false);
        $response->assertJsonPath('allow_new_application', false); // Button ajukan warga baru TIDAK MUNCUL
        $response->assertJsonPath('registered_name', 'Warga Belum Aktivasi');
    }

    public function test_check_citizen_endpoint_detects_not_found_nik_and_allows_new_application(): void
    {
        $response = $this->postJson('/api/v1/auth/check-citizen', [
            'nik' => '3171039999990001',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'NOT_FOUND');
        $response->assertJsonPath('allow_new_application', true); // Button ajukan warga baru MUNCUL
    }

    public function test_check_citizen_endpoint_detects_similar_names_in_rt_database(): void
    {
        Citizen::factory()->create([
            'nik' => '3171031111110001',
            'nik_hash' => hash('sha256', '3171031111110001'),
            'full_name' => 'Bambang Pamungkas',
        ]);

        $response = $this->postJson('/api/v1/auth/check-citizen', [
            'name' => 'Bambang',
        ]);

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'similar_citizens');
        $this->assertEquals('Bambang Pamungkas', $response->json('similar_citizens.0.name'));
    }

    public function test_admin_can_verify_pending_citizen_application(): void
    {
        $sekretaris = User::factory()->sekretaris()->create();
        $citizen = Citizen::factory()->create([
            'status_warga' => 'pending_verification',
        ]);

        $response = $this->actingAs($sekretaris)->postJson("/api/v1/admin/citizens/{$citizen->id}/verify");

        $response->assertStatus(200);
        $citizen->refresh();
        $this->assertEquals('tetap', $citizen->status_warga);
    }
}
