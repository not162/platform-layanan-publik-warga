<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Citizen;
use App\Models\CommunityEvent;
use App\Models\EmergencyContact;
use App\Models\FamilyCard;
use App\Models\FinanceTransaction;
use App\Models\RoundSchedule;
use App\Models\RtOfficer;
use App\Models\User;
use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Tests\TestCase;

class Sprint1BusinessArchitectureAndNavbarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Broadcast::extend('auth-evaluator', function () {
            $broadcaster = new class extends Broadcaster
            {
                public function auth($request)
                {
                    $channelName = preg_replace('/^(private|presence)-/', '', $request->channel_name);

                    return parent::verifyUserCanAccessChannel($request, $channelName);
                }

                public function validAuthenticationResponse($request, $result)
                {
                    return response()->json(['auth' => true]);
                }

                public function broadcast(array $channels, $event, array $payload = []): void {}
            };

            // Register channels
            $broadcaster->channel('App.Models.User.{id}', function ($user, $id) {
                return (int) $user->id === (int) $id;
            });

            $broadcaster->channel('users.{id}', function ($user, $id) {
                return (int) $user->id === (int) $id;
            });

            $broadcaster->channel('warga.{id}', function ($user, $id) {
                return (int) $user->id === (int) $id;
            });

            $broadcaster->channel('citizens.{citizenId}', function ($user, $citizenId) {
                if ($user->isSuperadmin() || $user->hasPermission('citizen.read') || $user->hasPermission('citizen.manage')) {
                    return true;
                }

                return $user->citizen && (int) $user->citizen->id === (int) $citizenId;
            });

            $broadcaster->channel('admin-channel', function ($user) {
                return $user->isSuperadmin()
                    || $user->isAdmin()
                    || $user->isSekretaris()
                    || $user->isKetuaRt()
                    || $user->hasPermission('citizen.manage')
                    || $user->hasPermission('citizen.read');
            });

            return $broadcaster;
        });

        config([
            'broadcasting.connections.auth-evaluator' => ['driver' => 'auth-evaluator'],
            'broadcasting.default' => 'auth-evaluator',
        ]);
    }

    public function test_warga_role_access_exposes_id_username_nik_alamat_tanggal_lahir_without_errors(): void
    {
        $user = User::factory()->warga()->create([
            'name' => 'Bambang Pamungkas',
            'email' => 'bambang@layanan.test',
        ]);

        $familyCard = FamilyCard::factory()->create([
            'address' => 'Jl. Kemayoran Raya No. 45 RT 01 RW 05',
            'rt' => '001',
            'rw' => '005',
        ]);

        $citizen = Citizen::factory()->create([
            'user_id' => $user->id,
            'family_card_id' => $familyCard->id,
            'nik' => '3171031605900001',
            'nik_hash' => hash('sha256', '3171031605900001'),
            'full_name' => 'Bambang Pamungkas',
            'date_of_birth' => '1990-05-16',
            'place_of_birth' => 'Jakarta',
            'gender' => 'Laki-laki',
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/me');

        $response->assertOk()
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.name', 'Bambang Pamungkas')
            ->assertJsonPath('data.user.username', 'Bambang Pamungkas')
            ->assertJsonPath('data.user.role', 'WARGA')
            ->assertJsonPath('data.citizen.id', $citizen->id)
            ->assertJsonPath('data.citizen.user_id', $user->id)
            ->assertJsonPath('data.citizen.nik_masked', '3171********0001')
            ->assertJsonPath('data.citizen.alamat', 'Jl. Kemayoran Raya No. 45 RT 01 RW 05')
            ->assertJsonPath('data.citizen.address', 'Jl. Kemayoran Raya No. 45 RT 01 RW 05')
            ->assertJsonPath('data.citizen.rt', '001')
            ->assertJsonPath('data.citizen.rw', '005')
            ->assertJsonPath('data.citizen.date_of_birth', '1990-05-16')
            ->assertJsonPath('data.citizen.tanggal_lahir', '1990-05-16');

        // Plaintext NIK must never be leaked
        $this->assertStringNotContainsString('3171031605900001', $response->getContent());
    }

    public function test_warga_can_login_using_16_digit_nik(): void
    {
        $user = User::factory()->warga()->create([
            'email' => 'warga.nik@layanan.test',
            'password' => bcrypt('password123'),
        ]);

        $familyCard = FamilyCard::factory()->create();

        Citizen::factory()->create([
            'user_id' => $user->id,
            'family_card_id' => $familyCard->id,
            'nik' => '3171031605900002',
            'nik_hash' => hash('sha256', '3171031605900002'),
            'full_name' => 'Warga Login NIK',
        ]);

        $response = $this->postJson('/api/v1/login', [
            'login' => '3171031605900002',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonPath('user.email', 'warga.nik@layanan.test')
            ->assertJsonStructure(['access_token', 'token_type', 'user']);
    }

    public function test_realtime_broadcast_channel_authorization_for_warga(): void
    {
        $user = User::factory()->warga()->create();
        $familyCard = FamilyCard::factory()->create();
        $citizen = Citizen::factory()->create([
            'user_id' => $user->id,
            'family_card_id' => $familyCard->id,
        ]);

        // Channel App.Models.User.{id}
        $this->actingAs($user)->postJson('/broadcasting/auth', [
            'channel_name' => 'private-App.Models.User.'.$user->id,
            'socket_id' => '1234.5678',
        ])->assertOk();

        // Channel users.{id}
        $this->actingAs($user)->postJson('/broadcasting/auth', [
            'channel_name' => 'private-users.'.$user->id,
            'socket_id' => '1234.5678',
        ])->assertOk();

        // Channel warga.{id}
        $this->actingAs($user)->postJson('/broadcasting/auth', [
            'channel_name' => 'private-warga.'.$user->id,
            'socket_id' => '1234.5678',
        ])->assertOk();

        // Channel citizens.{citizenId}
        $this->actingAs($user)->postJson('/broadcasting/auth', [
            'channel_name' => 'private-citizens.'.$citizen->id,
            'socket_id' => '1234.5678',
        ])->assertOk();

        // Warga cannot listen on admin-channel
        $this->actingAs($user)->postJson('/broadcasting/auth', [
            'channel_name' => 'private-admin-channel',
            'socket_id' => '1234.5678',
        ])->assertForbidden();
    }

    public function test_warga_dashboard_renders_all_seven_services_from_navigation_image(): void
    {
        $user = User::factory()->warga()->create();
        $familyCard = FamilyCard::factory()->create();
        Citizen::factory()->create([
            'user_id' => $user->id,
            'family_card_id' => $familyCard->id,
        ]);

        // Seed data for the 7 services from the image
        Announcement::factory()->create([
            'title' => 'Warta Gotong Royong RT 01',
            'is_published' => true,
        ]);

        FinanceTransaction::factory()->create([
            'category' => 'Iuran Warga',
            'type' => 'income',
            'amount_idr' => 500000,
            'amount' => 500000,
            'status' => 'published',
        ]);

        CommunityEvent::factory()->create([
            'title' => 'Senam Sehat Warga',
            'is_published' => true,
        ]);

        RoundSchedule::factory()->create([
            'day_of_week' => 'Senin',
            'officer_names' => ['Bripka Santoso'],
            'is_active' => true,
        ]);

        EmergencyContact::factory()->create([
            'name' => 'Polsek Kemayoran',
            'category' => 'polisi',
            'phone_number' => '021-4244444',
            'is_active' => true,
        ]);

        RtOfficer::factory()->create([
            'name' => 'Bapak Ketua RT',
            'position' => 'Ketua RT 01',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        // 7 services from the navigation image must be present
        $response->assertSee('Warta & Pengumuman Baru', false);
        $response->assertSee('Transparansi Kas Lingkungan', false);
        $response->assertSee('Form Pengaduan Warga', false);
        $response->assertSee('Agenda Kegiatan Warga', false);
        $response->assertSee('Jadwal Ronda & Kamling', false);
        $response->assertSee('Kontak Darurat 24 Jam', false);
        $response->assertSee('Struktur Pengurus RT 01', false);
    }

    public function test_warga_can_submit_complaint_via_web_dashboard(): void
    {
        $user = User::factory()->warga()->create();
        $familyCard = FamilyCard::factory()->create();
        Citizen::factory()->create([
            'user_id' => $user->id,
            'family_card_id' => $familyCard->id,
        ]);

        $response = $this->actingAs($user)->post('/dashboard/complaints', [
            'title' => 'Saluran Air Mampet di Gang 3',
            'category' => 'Kebersihan & Sampah',
            'street_name' => 'Gang Melati 3',
            'location_detail' => 'Depan No. 12',
            'description' => 'Genangan air mulai meluap setelah hujan deras kemarin sore.',
            'is_anonymous' => 1,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('complaints', [
            'user_id' => $user->id,
            'title' => 'Saluran Air Mampet di Gang 3',
            'is_anonymous' => true,
        ]);
    }
}
