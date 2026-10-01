<?php

namespace Tests\Feature;

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
        $dashboardResponse->assertSee('Portal Pengurus RT 01');
        $dashboardResponse->assertSee('Katalog Master Dokumen', false);
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
