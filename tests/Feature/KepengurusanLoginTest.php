<?php

namespace Tests\Feature;

use App\Models\Citizen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KepengurusanLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_ketua_rt_can_login_via_web_form_and_redirect_to_dashboard(): void
    {
        $ketuaRt = User::factory()->ketuaRt()->create([
            'email' => 'ketua_rt@warga.local',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'ketua_rt@warga.local',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($ketuaRt);

        $dashboardResponse = $this->actingAs($ketuaRt)->get('/dashboard');
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee('Portal Resmi Ketua RT 01 / RW 05');
        $dashboardResponse->assertSee('Antrean Pengesahan Surat Pengantar Warga', false);
    }

    public function test_web_login_supports_json_request_with_session_cookies(): void
    {
        $ketuaRt = User::factory()->ketuaRt()->create([
            'email' => 'ketua_rt@warga.local',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/login', [
            'email' => 'ketua_rt@warga.local',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'access_token',
            'token_type',
            'redirect_url',
            'user' => ['id', 'name', 'email', 'role', 'is_active'],
        ]);
        $this->assertAuthenticatedAs($ketuaRt);
    }

    public function test_sekretaris_accesses_dedicated_sekretaris_dashboard(): void
    {
        $sekretaris = User::factory()->sekretaris()->create([
            'email' => 'sekretaris@warga.local',
            'password' => bcrypt('secret123'),
        ]);

        $response = $this->actingAs($sekretaris)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Portal Resmi Sekretaris RT 01 / RW 05');
        $response->assertSee('Meja Verifikasi Berkas Surat Pengantar Warga');
    }

    public function test_bendahara_accesses_dedicated_bendahara_dashboard(): void
    {
        $bendahara = User::factory()->bendahara()->create([
            'email' => 'bendahara@warga.local',
            'password' => bcrypt('secret123'),
        ]);

        $response = $this->actingAs($bendahara)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Portal Resmi Bendahara RT 01 / RW 05');
        $response->assertSee('Saldo Kas RT Saat Ini');
    }

    public function test_keamanan_accesses_dedicated_keamanan_dashboard(): void
    {
        $keamanan = User::factory()->petugasKeamanan()->create([
            'email' => 'keamanan@warga.local',
            'password' => bcrypt('secret123'),
        ]);

        $response = $this->actingAs($keamanan)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Portal Resmi Petugas Keamanan RT 01');
        $response->assertSee('Tiket Laporan Insiden Keamanan');
    }

    public function test_authenticated_user_accesses_profile_page(): void
    {
        $user = User::factory()->ketuaRt()->create();

        $response = $this->actingAs($user)->get('/dashboard/profile');
        $response->assertStatus(200);
        $response->assertSee('Profil Akun', false);
        $response->assertSee($user->email);
        $response->assertSee('Informasi Identitas', false);
    }

    public function test_warga_can_update_contact_details_from_profile_page(): void
    {
        $user = User::factory()->warga()->create([
            'email' => 'before@example.com',
            'email_verified_at' => now(),
        ]);
        $citizen = Citizen::factory()->create([
            'user_id' => $user->id,
            'email' => $user->email,
            'phone' => '081234567890',
        ]);

        $response = $this->actingAs($user)->patch('/dashboard/profile', [
            'email' => 'warga@gmail.com',
            'phone' => '+6281234567890',
            'occupation' => 'Wiraswasta',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'warga@gmail.com',
            'email_verified_at' => null,
        ]);
        $this->assertDatabaseHas('citizens', [
            'id' => $citizen->id,
            'email' => 'warga@gmail.com',
            'phone' => '+6281234567890',
            'phone_verified_at' => null,
            'occupation' => 'Wiraswasta',
        ]);
    }

    public function test_authenticated_user_redirected_from_login_page(): void
    {
        $user = User::factory()->ketuaRt()->create();

        $response = $this->actingAs($user)->get('/login');
        $response->assertRedirect('/dashboard');
    }

    public function test_bendahara_and_sekretaris_can_login_via_api(): void
    {
        $bendahara = User::factory()->bendahara()->create([
            'email' => 'bendahara@warga.local',
            'password' => bcrypt('secret123'),
        ]);

        $sekretaris = User::factory()->sekretaris()->create([
            'email' => 'sekretaris@warga.local',
            'password' => bcrypt('secret123'),
        ]);

        $resBendahara = $this->postJson('/api/v1/login', [
            'email' => 'bendahara@warga.local',
            'password' => 'secret123',
        ]);
        $resBendahara->assertStatus(200);
        $resBendahara->assertJsonStructure(['access_token', 'token_type', 'user']);
        $this->assertEquals('BENDAHARA', strtoupper($resBendahara->json('user.role')));

        $resSekretaris = $this->postJson('/api/v1/login', [
            'email' => 'sekretaris@warga.local',
            'password' => 'secret123',
        ]);
        $resSekretaris->assertStatus(200);
        $this->assertEquals('SEKRETARIS', strtoupper($resSekretaris->json('user.role')));
    }

    public function test_login_supports_login_and_username_alias_fields(): void
    {
        User::factory()->ketuaRt()->create([
            'email' => 'ketuart@example.com',
            'password' => bcrypt('validpassword'),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'login' => 'ketuart@example.com',
            'password' => 'validpassword',
        ]);

        $response->assertStatus(200);
    }
}
