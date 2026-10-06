<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Citizen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthSelfRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_warga_can_self_register_even_if_nik_not_yet_in_database(): void
    {
        $payload = [
            'nik' => '3171031605030002',
            'name' => 'SWAN DARU TIRTA SANDHIKA',
            'email' => 'obryanswan@gmail.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/v1/register', $payload);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'access_token',
            'user' => ['id', 'name', 'email', 'role', 'citizen'],
        ]);

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
}
