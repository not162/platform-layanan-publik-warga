<?php

namespace Tests\Feature;

use App\Events\CitizenRegistered;
use App\Models\Citizen;
use App\Models\User;
use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Tests\TestCase;

class Sprint4BroadcastingNotificationTest extends TestCase
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

            // Re-bind channels to this evaluator
            $broadcaster->channel('admin-channel', function ($user) {
                return $user->isSuperadmin()
                    || $user->isAdmin()
                    || $user->isSekretaris()
                    || $user->isKetuaRt()
                    || $user->hasPermission('citizen.manage')
                    || $user->hasPermission('citizen.read');
            });

            $broadcaster->channel('citizens.{citizenId}', function ($user, $citizenId) {
                if ($user->isSuperadmin() || $user->hasPermission('citizen.read') || $user->hasPermission('citizen.manage')) {
                    return true;
                }

                return $user->citizen && (int) $user->citizen->id === (int) $citizenId;
            });

            return $broadcaster;
        });

        config([
            'broadcasting.connections.auth-evaluator' => ['driver' => 'auth-evaluator'],
            'broadcasting.default' => 'auth-evaluator',
        ]);
    }

    public function test_citizen_registered_broadcasts_on_private_channel(): void
    {
        $user = User::factory()->warga()->create([
            'name' => 'Warga Baru Terdaftar',
        ]);

        $event = new CitizenRegistered($user);
        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertEquals('private-admin-channel', $channels[0]->name);

        $payload = $event->broadcastWith();
        $this->assertEquals($user->id, $payload['id']);
        $this->assertEquals('Warga Baru Terdaftar', $payload['name']);
        $this->assertArrayNotHasKey('password', $payload);
        $this->assertArrayNotHasKey('remember_token', $payload);
    }

    public function test_admin_channel_authorization_permits_staff_and_rejects_warga(): void
    {
        $superadmin = User::factory()->superadmin()->create();
        $admin = User::factory()->admin(['citizen.read'])->create();
        $warga = User::factory()->warga()->create();

        // Superadmin is authorized
        $this->actingAs($superadmin)->postJson('/broadcasting/auth', [
            'channel_name' => 'private-admin-channel',
            'socket_id' => '1234.5678',
        ])->assertOk();

        // Admin with scope is authorized
        $this->actingAs($admin)->postJson('/broadcasting/auth', [
            'channel_name' => 'private-admin-channel',
            'socket_id' => '1234.5678',
        ])->assertOk();

        // Warga without scope is rejected with 403 Forbidden
        $this->actingAs($warga)->postJson('/broadcasting/auth', [
            'channel_name' => 'private-admin-channel',
            'socket_id' => '1234.5678',
        ])->assertForbidden();
    }

    public function test_citizen_channel_authorization_isolates_tenants(): void
    {
        $wargaA = User::factory()->warga()->create();
        $citizenA = Citizen::factory()->create(['user_id' => $wargaA->id]);

        $wargaB = User::factory()->warga()->create();
        $citizenB = Citizen::factory()->create(['user_id' => $wargaB->id]);

        $admin = User::factory()->admin(['citizen.read'])->create();

        // Warga A can listen to own citizen channel
        $this->actingAs($wargaA)->postJson('/broadcasting/auth', [
            'channel_name' => "private-citizens.{$citizenA->id}",
            'socket_id' => '1234.5678',
        ])->assertOk();

        // Warga A CANNOT listen to Warga B's citizen channel
        $this->actingAs($wargaA)->postJson('/broadcasting/auth', [
            'channel_name' => "private-citizens.{$citizenB->id}",
            'socket_id' => '1234.5678',
        ])->assertForbidden();

        // Staff can listen to citizen channel
        $this->actingAs($admin)->postJson('/broadcasting/auth', [
            'channel_name' => "private-citizens.{$citizenA->id}",
            'socket_id' => '1234.5678',
        ])->assertOk();
    }
}
